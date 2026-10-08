<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Free lightweight path: CPN help/search without a cloud provider API key.
 * General chat prefers a configured local model; never dumps unrelated CPN
 * route lists for non-panel questions.
 *
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_free_reply($message, array $cfg, $username)
{
    $message = trim((string) $message);
    $toolsUsed = [];
    $hits = mra_search_corpus($message, 6);
    $toolsUsed[] = ['tool' => 'search_menu', 'count' => count($hits)];
    $isCpn = mra_looks_like_cpn_query($message, $hits);

    $local = mra_try_local_chat($message, $isCpn ? $hits : [], $cfg);
    if (!empty($local['ok']) && !empty($local['reply'])) {
        return [
            'ok' => true,
            'reply' => (string) $local['reply'],
            'provider' => 'local',
            'model' => isset($local['model']) ? (string) $local['model'] : (string) ($cfg['local_model'] ?? ''),
            'tools_used' => $toolsUsed,
        ];
    }

    if (!empty($cfg['local_only_mode'])) {
        $base = (string) ($cfg['local_base_url'] ?? 'http://127.0.0.1:11434/v1');
        $err = isset($local['error']) ? (string) $local['error'] : 'Local model is not reachable.';
        return [
            'ok' => false,
            'error' => 'Local-only mode is on, but the local model failed: ' . $err
                . ' Point local_base_url at Ollama/LM Studio/Bionic on this server (e.g. ' . $base . ').',
            'provider' => 'local',
            'tools_used' => $toolsUsed,
        ];
    }

    if ($isCpn && !empty($hits)) {
        return [
            'ok' => true,
            'reply' => mra_format_help_reply($message, $hits),
            'provider' => 'free',
            'tools_used' => $toolsUsed,
        ];
    }

    return [
        'ok' => true,
        'reply' => mra_format_free_general_unavailable($message, $cfg),
        'provider' => 'free',
        'tools_used' => $toolsUsed,
    ];
}

/**
 * @param array<int,array<string,mixed>> $hits
 */
function mra_looks_like_cpn_query($message, array $hits)
{
    $q = strtolower(trim((string) $message));
    if ($q === '') {
        return false;
    }
    if (preg_match('/\b(where|how|open|find|show|manage|list|goto|go to)\b.{0,40}\b(website|websites|email|plugin|plugins|docker|package|packages|ssl|dns|firewall|fail2ban|php|user|users|backup|wordpress|mail|domain|subdomain|settings|sidebar|panel|cpn)\b/i', $q)) {
        return true;
    }
    if (preg_match('/\b(cpn|panel|websites?\/list|\/websites|\/email|\/plugins|\/docker|\/packages|open\s*litespeed|litespeed|phpmyadmin)\b/i', $q)) {
        return true;
    }
    if (empty($hits)) {
        return false;
    }
    return ((int) ($hits[0]['score'] ?? 0)) >= 6;
}

/**
 * @param array<int,array<string,mixed>> $hits
 */
function mra_format_help_reply($message, array $hits)
{
    $lines = [];
    $lines[] = 'Mr Agent free helper (CPN navigation; no cloud provider API key required).';
    $lines[] = '';
    if (empty($hits)) {
        $lines[] = 'I could not find a matching CPN menu item for: "' . $message . '".';
        $lines[] = 'Try words like websites, email, plugins, fail2ban, docker, ssl, users, or packages.';
        $lines[] = 'For general chat (dates, small talk), configure a local model on this server, or add a cloud provider API key.';
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
 * @param array<string,mixed> $cfg
 */
function mra_format_free_general_unavailable($message, array $cfg)
{
    $base = (string) ($cfg['local_base_url'] ?? 'http://127.0.0.1:11434/v1');
    $model = (string) ($cfg['local_model'] ?? 'llama3.2:1b');
    $lines = [];
    $lines[] = 'Mr Agent free helper only answers CPN Panel navigation (menus, routes, where to click).';
    $lines[] = '';
    $lines[] = 'Your question looks like general chat, not a panel lookup:';
    $lines[] = '"' . $message . '"';
    $lines[] = '';
    $lines[] = 'To answer this without a cloud provider API key:';
    $lines[] = '1. Install Ollama, LM Studio, or Bionic on the CPN server (not only on your Windows PC).';
    $lines[] = '2. Set Local base URL to something like ' . $base . ' (LM Studio often uses http://127.0.0.1:1235/v1).';
    $lines[] = '3. Set Local model to ' . $model . ' (or your pulled model name).';
    $lines[] = '4. Or pick Provider = Local in the full /mr-agent UI.';
    $lines[] = '';
    $lines[] = 'MCP skills are tools into CPN (search menus, list websites). They are not a substitute for a text-generating model.';
    return implode("\n", $lines);
}

/**
 * @param array<int,array<string,mixed>> $hits
 * @param array<string,mixed> $cfg
 * @return array{ok:bool,reply?:string,error?:string,model?:string}
 */
function mra_try_local_chat($message, array $hits, array $cfg)
{
    $base = rtrim((string) ($cfg['local_base_url'] ?? ''), '/');
    if ($base === '') {
        return ['ok' => false, 'error' => 'Local base URL not set'];
    }
    if (function_exists('mra_local_base_allowed')) {
        if (!mra_local_base_allowed($base, $cfg)) {
            return ['ok' => false, 'error' => 'Local base URL blocked by policy'];
        }
    } elseif (!preg_match('#^https?://(127\.0\.0\.1|localhost|::1)(:\d+)?(/|$)#i', $base)) {
        return ['ok' => false, 'error' => 'Local base URL blocked by policy'];
    }
    $model = (string) ($cfg['local_model'] ?? 'llama3.2:1b');
    $context = '';
    foreach (array_slice($hits, 0, 5) as $hit) {
        $context .= '- ' . ($hit['title'] ?? '') . ' (' . ($hit['path'] ?? '') . '): ' . ($hit['help'] ?? '') . "\n";
    }
    $system = 'You are Mr Agent, a helpful assistant inside CPN Panel. '
        . 'Answer briefly and accurately. '
        . 'If CPN route hints are provided, use them for panel questions. '
        . 'For general questions (date, small talk), answer normally. '
        . 'Never invent destructive admin actions. Never ask for or repeat API keys.';
    $user = "User question:\n" . $message;
    if ($context !== '') {
        $user .= "\n\nCPN route hints:\n" . $context;
    }
    if (!function_exists('mra_local_chat_messages')) {
        return ['ok' => false, 'error' => 'Local endpoint helper missing'];
    }
    $timeout = min(15, (int) ($cfg['local_timeout_seconds'] ?? 45));
    return mra_local_chat_messages(
        [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        $cfg,
        $model,
        $timeout
    );
}
