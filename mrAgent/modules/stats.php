<?php
/**
 * Privacy-safe Mr Agent usage statistics (no message bodies, no secrets).
 */
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Format unix timestamp as European dd/mm/yyyy HH:mm (UTC wall clock).
 */
function mra_format_eu_datetime($ts)
{
    $ts = (int) $ts;
    if ($ts <= 0) {
        return '';
    }
    return gmdate('d/m/Y H:i', $ts);
}

/**
 * @return array<string,mixed>
 */
function mra_collect_stats(array $cfg, $domain = null)
{
    $domain = $domain !== null ? $domain : ($cfg['domain'] ?? null);
    $dir = mra_chats_dir($domain);
    $now = mra_now_unix();
    $cut7 = $now - (7 * 86400);
    $cut30 = $now - (30 * 86400);
    $users = [];
    $lastTs = 0;
    $conversationsTotal = 0;
    $messagesTotal = 0;
    $conversations7d = 0;
    $messages7d = 0;
    $conversations30d = 0;
    $messages30d = 0;
    $storageBytes = mra_chats_disk_bytes($domain);
    $limitMb = (int) ($cfg['max_chat_disk_mb'] ?? 50);

    if (is_dir($dir)) {
        $dh = @opendir($dir);
        if ($dh !== false) {
            while (($name = readdir($dh)) !== false) {
                if ($name === '.' || $name === '..' || substr($name, -5) !== '.json') {
                    continue;
                }
                $path = $dir . '/' . $name;
                if (!is_file($path)) {
                    continue;
                }
                $raw = @file_get_contents($path);
                $data = json_decode((string) $raw, true);
                if (!is_array($data)) {
                    continue;
                }
                $conversationsTotal++;
                $user = strtolower(trim((string) ($data['user'] ?? '')));
                if ($user !== '') {
                    $users[$user] = true;
                }
                $updated = isset($data['updated_at']) ? (int) $data['updated_at'] : (int) @filemtime($path);
                if ($updated > $lastTs) {
                    $lastTs = $updated;
                }
                $messages = isset($data['messages']) && is_array($data['messages']) ? $data['messages'] : [];
                $msgCount = count($messages);
                $messagesTotal += $msgCount;
                $msg7 = 0;
                $msg30 = 0;
                foreach ($messages as $m) {
                    $ts = isset($m['ts']) ? (int) $m['ts'] : $updated;
                    if ($ts >= $cut7) {
                        $msg7++;
                    }
                    if ($ts >= $cut30) {
                        $msg30++;
                    }
                }
                $messages7d += $msg7;
                $messages30d += $msg30;
                if ($updated >= $cut7) {
                    $conversations7d++;
                }
                if ($updated >= $cut30) {
                    $conversations30d++;
                }
            }
            closedir($dh);
        }
    }

    $empty = ($conversationsTotal === 0 && $storageBytes === 0);
    return [
        'ok' => true,
        'scope' => (is_string($domain) && $domain !== '' && strtolower($domain) !== '_host') ? 'site' : 'host',
        'domain' => (string) ($domain ?: '_host'),
        'conversations_total' => $conversationsTotal,
        'messages_total' => $messagesTotal,
        'conversations_7d' => $conversations7d,
        'messages_7d' => $messages7d,
        'conversations_30d' => $conversations30d,
        'messages_30d' => $messages30d,
        'distinct_users' => count($users),
        'storage_bytes' => $storageBytes,
        'storage_mb' => round($storageBytes / 1048576, 2),
        'storage_limit_mb' => max(1, $limitMb),
        'last_activity' => mra_format_eu_datetime($lastTs),
        'empty' => $empty,
    ];
}
