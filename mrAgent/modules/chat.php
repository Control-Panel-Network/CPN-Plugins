<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Main chat entry (non-stream MVP).
 *
 * Providers: free/auto (smart local + CPN help), local, openai, anthropic, custom.
 * MCP skills are tools; providers generate text.
 *
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_chat_handle($message, $provider, $model, array $cfg, $username, $conversationId = '')
{
    $cfg = mra_normalize_limits($cfg);
    $message = trim((string) $message);
    $maxLen = (int) ($cfg['max_message_length'] ?? 4000);
    if ($message === '' || strlen($message) > $maxLen) {
        return ['ok' => false, 'error' => 'Message must be between 1 and ' . $maxLen . ' characters.'];
    }
    if (preg_match('/\b(rm\s+-rf|curl\s+|wget\s+|powershell\b|Invoke-Expression)\b/i', $message)) {
        mra_log('blocked_shellish_prompt', ['user' => $username]);
    }

    $provider = strtolower(trim((string) $provider));
    // auto / empty: smart free path (local for general chat, CPN help for panel Qs).
    // Explicit default_provider is for the full UI select, not float auto.
    if ($provider === '' || $provider === 'auto') {
        $provider = 'free';
    }
    if (!empty($cfg['local_only_mode'])) {
        $provider = 'local';
    }
    $allowed = ['free', 'openai', 'anthropic', 'custom', 'local'];
    if (!in_array($provider, $allowed, true)) {
        return ['ok' => false, 'error' => 'Unknown provider.'];
    }
    if ($model === '') {
        $model = mra_default_model_for($provider);
    }

    list($concOk, $concErr, $lockFh) = mra_concurrent_acquire($cfg, $username);
    if (!$concOk) {
        return ['ok' => false, 'error' => $concErr];
    }

    try {
        list($rlOk, $rlErr) = mra_rate_limit_check($cfg, $username);
        if (!$rlOk) {
            mra_concurrent_release($lockFh);
            return ['ok' => false, 'error' => $rlErr];
        }

        if ($provider === 'free') {
            $result = mra_free_reply($message, $cfg, $username);
        } else {
            $system = 'You are Mr Agent, a friendly AI assistant inside CPN Panel. '
                . 'Three layers: (1) Provider = who generates text (local model or cloud API keys). '
                . '(2) MCP = panel-wide tool protocol. (3) Skills = per-area modules. '
                . 'Call list_skills to discover tools. Prefer Help skill for "where is X". '
                . 'Use list_websites / list_packages only when the user asks for inventory (owner tools). '
                . 'Never invent destructive admin actions. Never ask users to paste provider API keys into chat. '
                . 'Call keys "provider API keys", not MCP keys.';

            if ($provider === 'local') {
                $result = mra_chat_local($message, $model, $cfg, $username, $system);
            } elseif ($provider === 'anthropic') {
                $result = mra_chat_anthropic_with_tools($message, $model, $cfg, $username, $system);
            } else {
                $result = mra_chat_openai_with_tools($message, $provider, $model, $cfg, $username, $system);
            }
        }

        if (!empty($result['ok']) && isset($result['reply'])) {
            $store = mra_conversation_append(
                $cfg,
                $username,
                $conversationId,
                $message,
                (string) $result['reply'],
                (string) ($result['provider'] ?? $provider)
            );
            if (!empty($store['conversation_id'])) {
                $result['conversation_id'] = $store['conversation_id'];
            }
            if (!empty($store['pruned']['deleted'])) {
                $result['pruned'] = (int) $store['pruned']['deleted'];
            }
        } else {
            mra_prune_storage($cfg, $cfg['domain'] ?? null);
        }

        mra_concurrent_release($lockFh);
        return $result;
    } catch (Throwable $e) {
        mra_concurrent_release($lockFh);
        mra_log('chat_handle_failed', ['error' => mra_redact($e->getMessage())]);
        return ['ok' => false, 'error' => 'Chat failed'];
    }
}

/**
 * Local OpenAI-compatible path (Ollama / LM Studio / Bionic). Plain chat first.
 *
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_chat_local($message, $model, array $cfg, $username, $system)
{
    $ep = mra_local_endpoint_cfg($cfg);
    if ($model === '' || $model === 'cpn-help') {
        $model = $ep['model'];
    }
    if (empty($ep['allow_private'])) {
        return [
            'ok' => false,
            'error' => 'Local base URL is not allowed. Use http://127.0.0.1:11434/v1 (Ollama), '
                . 'http://127.0.0.1:1235/v1 (LM Studio / Bionic), or enable Local allow LAN.',
            'provider' => 'local',
        ];
    }

    $hits = mra_search_corpus($message, 5);
    $hint = '';
    if (function_exists('mra_looks_like_cpn_query') && mra_looks_like_cpn_query($message, $hits) && !empty($hits)) {
        foreach (array_slice($hits, 0, 4) as $hit) {
            $hint .= '- ' . ($hit['title'] ?? '') . ' → ' . ($hit['path'] ?? '') . "\n";
        }
    }
    $userContent = $message;
    if ($hint !== '') {
        $userContent .= "\n\nRelevant CPN routes:\n" . $hint;
    }
    $plain = mra_local_chat_messages(
        [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userContent],
        ],
        $cfg,
        $model,
        (int) ($cfg['local_timeout_seconds'] ?? 45)
    );
    if (!empty($plain['ok'])) {
        return [
            'ok' => true,
            'reply' => (string) $plain['reply'],
            'provider' => 'local',
            'model' => $model,
            'tools_used' => [],
        ];
    }

    $err = isset($plain['error']) ? (string) $plain['error'] : 'Local model request failed';
    return [
        'ok' => false,
        'error' => $err . ' Ensure the local LLM listens on ' . $ep['base']
            . ' on this CPN server (browser Windows Ollama is not reachable unless you expose it and enable Local allow LAN).',
        'provider' => 'local',
    ];
}

/**
 * @return array<string,mixed>
 */
function mra_chat_openai_with_tools($message, $provider, $model, array $cfg, $username, $system)
{
    $apiKey = mra_resolve_api_key($provider, $username, $cfg);
    $base = 'https://api.openai.com/v1';
    $allowPrivate = false;
    $timeout = 60;
    $maxBytes = 0;
    if ($provider === 'custom') {
        $store = mra_keys_store($cfg['domain'] ?? null);
        $base = (string) ($cfg['custom_base_url'] ?? '');
        if ($base === '' && !empty($store['host']['custom_base_url'])) {
            $base = (string) $store['host']['custom_base_url'];
        }
        if ($base === '') {
            return ['ok' => false, 'error' => 'Set a custom OpenAI-compatible base URL in owner settings.'];
        }
    }

    $maxTokens = (int) ($cfg['max_tokens_per_reply'] ?? 1024);
    $messages = [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $message],
    ];
    $toolsUsed = [];
    $maxRounds = 3;
    for ($i = 0; $i < $maxRounds; $i++) {
        $resp = mra_openai_chat($messages, $model, $apiKey, $base, true, $maxTokens, $timeout, $allowPrivate, $maxBytes);
        if (empty($resp['ok'])) {
            return ['ok' => false, 'error' => $resp['error'] ?? 'Provider error', 'provider' => $provider];
        }
        $body = $resp['raw'];
        $msg = mra_openai_extract_message(is_array($body) ? $body : []);
        $toolCalls = isset($msg['tool_calls']) && is_array($msg['tool_calls']) ? $msg['tool_calls'] : [];
        if (empty($toolCalls)) {
            $text = isset($msg['content']) ? trim((string) $msg['content']) : '';
            if ($text === '') {
                return ['ok' => false, 'error' => 'Empty model reply', 'provider' => $provider];
            }
            return ['ok' => true, 'reply' => $text, 'provider' => $provider, 'model' => $model, 'tools_used' => $toolsUsed];
        }
        $messages[] = $msg;
        foreach ($toolCalls as $tc) {
            $fn = isset($tc['function']['name']) ? (string) $tc['function']['name'] : '';
            $argsRaw = isset($tc['function']['arguments']) ? (string) $tc['function']['arguments'] : '{}';
            $args = json_decode($argsRaw, true);
            if (!is_array($args)) {
                $args = [];
            }
            $result = mra_tool_execute($fn, $args, $cfg, $username);
            $toolsUsed[] = ['tool' => $fn, 'ok' => !empty($result['ok'])];
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => (string) ($tc['id'] ?? ('call_' . $i)),
                'content' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
        }
    }
    return ['ok' => false, 'error' => 'Tool loop limit reached', 'provider' => $provider, 'tools_used' => $toolsUsed];
}

/**
 * @return array<string,mixed>
 */
function mra_chat_anthropic_with_tools($message, $model, array $cfg, $username, $system)
{
    $apiKey = mra_resolve_api_key('anthropic', $username, $cfg);
    $maxTokens = (int) ($cfg['max_tokens_per_reply'] ?? 1024);
    $messages = [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $message],
    ];
    $toolsUsed = [];
    $resp = mra_anthropic_chat($messages, $model, $apiKey, true, $maxTokens);
    if (empty($resp['ok'])) {
        return ['ok' => false, 'error' => $resp['error'] ?? 'Anthropic error', 'provider' => 'anthropic'];
    }
    $body = is_array($resp['raw']) ? $resp['raw'] : [];
    $uses = mra_anthropic_extract_tool_uses($body);
    if (!empty($uses)) {
        $toolResults = [];
        foreach ($uses as $use) {
            $result = mra_tool_execute($use['name'], $use['input'], $cfg, $username);
            $toolsUsed[] = ['tool' => $use['name'], 'ok' => !empty($result['ok'])];
            $toolResults[] = $result;
        }
        $follow = $message . "\n\nTool results (JSON):\n" . json_encode($toolResults, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . "\n\nAnswer the user using these results.";
        $resp2 = mra_anthropic_chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $follow],
        ], $model, $apiKey, false, $maxTokens);
        if (empty($resp2['ok'])) {
            return ['ok' => false, 'error' => $resp2['error'] ?? 'Anthropic follow-up failed', 'provider' => 'anthropic', 'tools_used' => $toolsUsed];
        }
        $text = mra_anthropic_extract_text(is_array($resp2['raw']) ? $resp2['raw'] : []);
        if ($text === '') {
            $text = mra_format_help_reply($message, mra_search_corpus($message, 6));
        }
        return ['ok' => true, 'reply' => $text, 'provider' => 'anthropic', 'model' => $model, 'tools_used' => $toolsUsed];
    }
    $text = mra_anthropic_extract_text($body);
    if ($text === '') {
        return ['ok' => false, 'error' => 'Empty Anthropic reply', 'provider' => 'anthropic'];
    }
    return ['ok' => true, 'reply' => $text, 'provider' => 'anthropic', 'model' => $model, 'tools_used' => $toolsUsed];
}
