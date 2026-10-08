<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Main chat entry (non-stream MVP).
 *
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_chat_handle($message, $provider, $model, array $cfg, $username)
{
    $message = trim((string) $message);
    if ($message === '' || strlen($message) > 8000) {
        return ['ok' => false, 'error' => 'Message must be between 1 and 8000 characters.'];
    }
    // Soft prompt-injection guard: never pass messages to shell.
    if (preg_match('/\b(rm\s+-rf|curl\s+|wget\s+|powershell\b|Invoke-Expression)\b/i', $message)) {
        mra_log('blocked_shellish_prompt', ['user' => $username]);
        // Still answer via free help; do not execute anything.
    }

    $provider = strtolower(trim((string) $provider));
    if ($provider === '') {
        $provider = strtolower((string) ($cfg['default_provider'] ?? 'free'));
    }
    $allowed = ['free', 'openai', 'anthropic', 'custom', 'local'];
    if (!in_array($provider, $allowed, true)) {
        return ['ok' => false, 'error' => 'Unknown provider.'];
    }
    if ($model === '') {
        $model = mra_default_model_for($provider);
    }

    list($rlOk, $rlErr) = mra_rate_limit_check($cfg, $username);
    if (!$rlOk) {
        return ['ok' => false, 'error' => $rlErr];
    }

    if ($provider === 'free') {
        return mra_free_reply($message, $cfg, $username);
    }

    $system = 'You are Mr Agent, a friendly AI assistant inside CPN Panel. '
        . 'MCP is the panel-wide tool protocol. Skills are per-area modules (Help, Websites, Packages, Email, DNS, etc.). '
        . 'Call list_skills to discover tools. Prefer Help skill for "where is X". '
        . 'Use list_websites / list_packages only when the user asks for inventory (owner tools). '
        . 'Never invent destructive admin actions. Never ask users to paste provider API keys into chat. '
        . 'Call keys "provider API keys", not MCP keys.';

    if ($provider === 'anthropic') {
        return mra_chat_anthropic_with_tools($message, $model, $cfg, $username, $system);
    }

    return mra_chat_openai_with_tools($message, $provider, $model, $cfg, $username, $system);
}

/**
 * @return array<string,mixed>
 */
function mra_chat_openai_with_tools($message, $provider, $model, array $cfg, $username, $system)
{
    $apiKey = mra_resolve_api_key($provider, $username, $cfg);
    $base = 'https://api.openai.com/v1';
    if ($provider === 'custom') {
        $store = mra_keys_store($cfg['domain'] ?? null);
        $base = (string) ($cfg['custom_base_url'] ?? '');
        if ($base === '' && !empty($store['host']['custom_base_url'])) {
            $base = (string) $store['host']['custom_base_url'];
        }
        if ($base === '') {
            return ['ok' => false, 'error' => 'Set a custom OpenAI-compatible base URL in owner settings.'];
        }
    } elseif ($provider === 'local') {
        $base = (string) ($cfg['local_base_url'] ?? 'http://127.0.0.1:11434/v1');
        $apiKey = mra_resolve_api_key('local', $username, $cfg);
    }

    $messages = [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $message],
    ];
    $toolsUsed = [];
    $maxRounds = 3;
    for ($i = 0; $i < $maxRounds; $i++) {
        $resp = mra_openai_chat($messages, $model, $apiKey, $base, true);
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
    $messages = [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $message],
    ];
    $toolsUsed = [];
    $resp = mra_anthropic_chat($messages, $model, $apiKey, true);
    if (empty($resp['ok'])) {
        return ['ok' => false, 'error' => $resp['error'] ?? 'Anthropic error', 'provider' => 'anthropic'];
    }
    $body = is_array($resp['raw']) ? $resp['raw'] : [];
    $uses = mra_anthropic_extract_tool_uses($body);
    if (!empty($uses)) {
        // One tool round then ask for final text (simplified MVP).
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
        ], $model, $apiKey, false);
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
