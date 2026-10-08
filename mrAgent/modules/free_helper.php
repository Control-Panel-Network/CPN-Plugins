<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Free lightweight path: CPN-aware help/search without a paid provider API key.
 * Optionally tries a local OpenAI-compatible endpoint if configured and reachable.
 *
 * @param array<string,mixed> $cfg
 * @return array{ok:bool,reply:string,provider:string,tools_used:array}
 */
function mra_free_reply($message, array $cfg, $username)
{
    $message = trim((string) $message);
    $toolsUsed = [];

    // Always run corpus search for grounding.
    $hits = mra_search_corpus($message, 6);
    $toolsUsed[] = ['tool' => 'search_menu', 'count' => count($hits)];

    // Optional tiny local model (Ollama / LM Studio style). Fail soft.
    $local = mra_try_local_chat($message, $hits, $cfg);
    if (!empty($local['ok']) && !empty($local['reply'])) {
        return [
            'ok' => true,
            'reply' => (string) $local['reply'],
            'provider' => 'local',
            'tools_used' => $toolsUsed,
        ];
    }

    $reply = mra_format_help_reply($message, $hits);
    return [
        'ok' => true,
        'reply' => $reply,
        'provider' => 'free',
        'tools_used' => $toolsUsed,
    ];
}

/**
 * @param array<int,array<string,mixed>> $hits
 */
function mra_format_help_reply($message, array $hits)
{
    $lines = [];
    $lines[] = 'Mr Agent free helper (no provider API key required).';
    $lines[] = '';
    if (empty($hits)) {
        $lines[] = 'I could not find a matching CPN menu item for: "' . $message . '".';
        $lines[] = 'Try words like websites, email, plugins, fail2ban, docker, ssl, users, or packages.';
        $lines[] = 'For richer answers, add an OpenAI or Anthropic provider API key in Settings.';
        return implode("\n", $lines);
    }
    $lines[] = 'Here is where to look in CPN Panel:';
    $lines[] = '';
    foreach ($hits as $i => $hit) {
        $n = $i + 1;
        $lines[] = $n . '. ' . ($hit['title'] ?? 'Item') . ' → ' . ($hit['path'] ?? '');
        if (!empty($hit['help'])) {
            $lines[] = '   ' . $hit['help'];
        }
    }
    $lines[] = '';
    $lines[] = 'Tip: open Plugins → Store to install feature unlocks (for example mtaSts, bimi, fail2ban).';
    return implode("\n", $lines);
}

/**
 * Best-effort local OpenAI-compatible call. Never throws to the user as a hard failure.
 *
 * @param array<int,array<string,mixed>> $hits
 * @return array{ok:bool,reply?:string}
 */
function mra_try_local_chat($message, array $hits, array $cfg)
{
    $base = rtrim((string) ($cfg['local_base_url'] ?? ''), '/');
    if ($base === '') {
        return ['ok' => false];
    }
    // Only allow loopback for free/local path to avoid SSRF.
    if (!preg_match('#^https?://(127\.0\.0\.1|localhost)(:\d+)?(/|$)#i', $base)) {
        return ['ok' => false];
    }
    $model = (string) ($cfg['local_model'] ?? 'llama3.2:1b');
    $context = '';
    foreach (array_slice($hits, 0, 5) as $hit) {
        $context .= '- ' . ($hit['title'] ?? '') . ' (' . ($hit['path'] ?? '') . '): ' . ($hit['help'] ?? '') . "\n";
    }
    $system = 'You are Mr Agent, a helpful CPN Panel assistant. Answer briefly using the route hints. Never invent destructive actions. Never ask for or repeat API keys.';
    $user = "User question:\n" . $message . "\n\nCPN route hints:\n" . $context;
    $maxTokens = min(500, (int) ($cfg['max_tokens_per_reply'] ?? 1024));
    $timeout = min(15, (int) ($cfg['local_timeout_seconds'] ?? 45));
    $maxBytes = (int) ($cfg['local_max_response_bytes'] ?? 1048576);
    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        'temperature' => 0.2,
        'max_tokens' => max(64, $maxTokens),
    ];
    $headers = ['Content-Type: application/json'];
    $key = (string) ($cfg['local_api_key'] ?? '');
    if ($key !== '') {
        $headers[] = 'Authorization: Bearer ' . $key;
    }
    $resp = mra_http_json('POST', $base . '/chat/completions', $payload, $headers, max(5, $timeout), false, $maxBytes);
    if (empty($resp['ok'])) {
        return ['ok' => false];
    }
    $body = $resp['body'];
    $text = '';
    if (isset($body['choices'][0]['message']['content'])) {
        $text = (string) $body['choices'][0]['message']['content'];
    }
    if ($text === '') {
        return ['ok' => false];
    }
    return ['ok' => true, 'reply' => $text];
}
