<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Chat log / conversation storage under /var/lib/cpn/mr-agent/<domain>/chats/
 * plus opportunistic prune for retention and disk caps.
 */

/**
 * @return array<string,int|float>
 */
function mra_limit_defaults()
{
    return [
        'rate_limit_per_hour' => 60,
        'max_history_messages' => 100,
        'max_stored_conversations' => 200,
        'chat_retention_days' => 30,
        'max_chat_disk_mb' => 50,
        'max_tokens_per_reply' => 1024,
        'max_message_length' => 4000,
        'concurrent_requests' => 2,
        'local_timeout_seconds' => 45,
        'local_max_response_bytes' => 1048576,
        'max_upload_bytes' => 262144,
    ];
}

/**
 * Clamp owner/panel limit values to safe ranges.
 *
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_normalize_limits(array $cfg)
{
    $d = mra_limit_defaults();
    $cfg['rate_limit_per_hour'] = max(1, min(10000, (int) ($cfg['rate_limit_per_hour'] ?? $d['rate_limit_per_hour'])));
    $cfg['max_history_messages'] = max(10, min(500, (int) ($cfg['max_history_messages'] ?? $d['max_history_messages'])));
    $cfg['max_stored_conversations'] = max(10, min(5000, (int) ($cfg['max_stored_conversations'] ?? $d['max_stored_conversations'])));
    $cfg['chat_retention_days'] = max(1, min(3650, (int) ($cfg['chat_retention_days'] ?? $d['chat_retention_days'])));
    $cfg['max_chat_disk_mb'] = max(1, min(10240, (int) ($cfg['max_chat_disk_mb'] ?? $d['max_chat_disk_mb'])));
    $cfg['max_tokens_per_reply'] = max(64, min(8192, (int) ($cfg['max_tokens_per_reply'] ?? $d['max_tokens_per_reply'])));
    $cfg['max_message_length'] = max(256, min(32000, (int) ($cfg['max_message_length'] ?? $d['max_message_length'])));
    $cfg['concurrent_requests'] = max(1, min(2, (int) ($cfg['concurrent_requests'] ?? $d['concurrent_requests'])));
    $cfg['local_timeout_seconds'] = max(5, min(300, (int) ($cfg['local_timeout_seconds'] ?? $d['local_timeout_seconds'])));
    $cfg['local_max_response_bytes'] = max(65536, min(16777216, (int) ($cfg['local_max_response_bytes'] ?? $d['local_max_response_bytes'])));
    $cfg['max_upload_bytes'] = max(4096, min(2097152, (int) ($cfg['max_upload_bytes'] ?? $d['max_upload_bytes'])));
    return $cfg;
}

function mra_chats_dir($domain = null)
{
    $dir = mra_var_lib_dir($domain) . '/chats';
    mra_safe_mkdir($dir, 0700);
    return $dir;
}

function mra_locks_dir($domain = null)
{
    $dir = mra_var_lib_dir($domain) . '/locks';
    mra_safe_mkdir($dir, 0700);
    return $dir;
}

/**
 * Sanitize conversation id for filenames.
 */
function mra_sanitize_conversation_id($id)
{
    $id = strtolower(trim((string) $id));
    $id = preg_replace('/[^a-z0-9_\-]/', '', $id);
    if ($id === '' || strlen($id) > 80) {
        return '';
    }
    return $id;
}

/**
 * Resolve conversation id for this request (client id or session-backed).
 */
function mra_conversation_id_for_request($requested, $username)
{
    $id = mra_sanitize_conversation_id($requested);
    if ($id !== '') {
        return $id;
    }
    $user = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim((string) $username))) ?: 'anon';
    $sid = '';
    if (session_status() === PHP_SESSION_ACTIVE) {
        $sid = substr(hash('sha256', (string) session_id()), 0, 16);
    }
    if ($sid === '') {
        $sid = substr(hash('sha256', $user . '|' . gmdate('Ymd') . '|' . mra_client_ip()), 0, 16);
    }
    return 'c_' . $user . '_' . $sid;
}

/**
 * Bytes used by chat logs for a domain (chats/ only).
 */
function mra_chats_disk_bytes($domain = null)
{
    $dir = mra_chats_dir($domain);
    $total = 0;
    if (!is_dir($dir)) {
        return 0;
    }
    $dh = @opendir($dir);
    if ($dh === false) {
        return 0;
    }
    while (($name = readdir($dh)) !== false) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if (substr($name, -5) !== '.json') {
            continue;
        }
        $path = $dir . '/' . $name;
        if (is_file($path)) {
            $total += (int) @filesize($path);
        }
    }
    closedir($dh);
    return $total;
}

/**
 * @return array{ok:bool,error?:string,conversation_id?:string,pruned?:array}
 */
function mra_conversation_append(array $cfg, $username, $conversationId, $userMessage, $assistantReply, $provider = '')
{
    $domain = $cfg['domain'] ?? null;
    $dir = mra_chats_dir($domain);
    $id = mra_conversation_id_for_request($conversationId, $username);
    $path = $dir . '/' . $id . '.json';
    $now = mra_now_unix();
    $data = [
        'id' => $id,
        'user' => strtolower(trim((string) $username)),
        'created_at' => $now,
        'updated_at' => $now,
        'provider' => (string) $provider,
        'messages' => [],
    ];
    if (is_file($path)) {
        $raw = @file_get_contents($path);
        $prev = json_decode((string) $raw, true);
        if (is_array($prev)) {
            $data['created_at'] = isset($prev['created_at']) ? (int) $prev['created_at'] : $now;
            $data['messages'] = isset($prev['messages']) && is_array($prev['messages']) ? $prev['messages'] : [];
        }
    }
    $data['messages'][] = [
        'role' => 'user',
        'content' => (string) $userMessage,
        'ts' => $now,
    ];
    $data['messages'][] = [
        'role' => 'assistant',
        'content' => (string) $assistantReply,
        'ts' => $now,
        'provider' => (string) $provider,
    ];
    $maxHist = (int) ($cfg['max_history_messages'] ?? 100);
    if (count($data['messages']) > $maxHist) {
        $data['messages'] = array_values(array_slice($data['messages'], -$maxHist));
    }
    $data['updated_at'] = $now;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return ['ok' => false, 'error' => 'Could not encode conversation'];
    }
    $ok = @file_put_contents($path, $json, LOCK_EX) !== false;
    if ($ok) {
        @chmod($path, 0600);
    }
    $pruned = mra_prune_storage($cfg, $domain);
    return [
        'ok' => $ok,
        'conversation_id' => $id,
        'pruned' => $pruned,
    ];
}

/**
 * Prune by age, conversation count, and disk MB cap.
 *
 * @return array{deleted:int,bytes_before:int,bytes_after:int,reason:array<int,string>}
 */
function mra_prune_storage(array $cfg, $domain = null)
{
    $dir = mra_chats_dir($domain);
    $retentionDays = (int) ($cfg['chat_retention_days'] ?? 30);
    $maxConvs = (int) ($cfg['max_stored_conversations'] ?? 200);
    $maxMb = (int) ($cfg['max_chat_disk_mb'] ?? 50);
    $maxBytes = $maxMb * 1024 * 1024;
    $cutoff = mra_now_unix() - ($retentionDays * 86400);
    $bytesBefore = mra_chats_disk_bytes($domain);
    $deleted = 0;
    $reasons = [];

    $files = [];
    $dh = @opendir($dir);
    if ($dh === false) {
        return ['deleted' => 0, 'bytes_before' => $bytesBefore, 'bytes_after' => $bytesBefore, 'reason' => []];
    }
    while (($name = readdir($dh)) !== false) {
        if ($name === '.' || $name === '..' || substr($name, -5) !== '.json') {
            continue;
        }
        $path = $dir . '/' . $name;
        if (!is_file($path)) {
            continue;
        }
        $mtime = (int) @filemtime($path);
        $size = (int) @filesize($path);
        $updated = $mtime;
        $raw = @file_get_contents($path);
        $meta = json_decode((string) $raw, true);
        if (is_array($meta) && isset($meta['updated_at'])) {
            $updated = (int) $meta['updated_at'];
        }
        $files[] = [
            'path' => $path,
            'updated' => $updated,
            'size' => $size,
        ];
    }
    closedir($dh);

    // Age prune.
    $keep = [];
    foreach ($files as $f) {
        if ($f['updated'] < $cutoff) {
            if (@unlink($f['path'])) {
                $deleted++;
            }
        } else {
            $keep[] = $f;
        }
    }
    if ($deleted > 0) {
        $reasons[] = 'retention_days';
    }

    // Count prune (oldest first).
    usort($keep, function ($a, $b) {
        return $a['updated'] <=> $b['updated'];
    });
    while (count($keep) > $maxConvs) {
        $f = array_shift($keep);
        if ($f && @unlink($f['path'])) {
            $deleted++;
            $reasons[] = 'max_conversations';
        }
    }

    // Disk cap prune (oldest first).
    $bytes = 0;
    foreach ($keep as $f) {
        $bytes += (int) $f['size'];
    }
    // Recompute sizes after age/count deletes.
    $bytes = mra_chats_disk_bytes($domain);
    while ($bytes > $maxBytes && !empty($keep)) {
        $f = array_shift($keep);
        if (!$f) {
            break;
        }
        $sz = (int) $f['size'];
        if (@unlink($f['path'])) {
            $deleted++;
            $bytes = max(0, $bytes - $sz);
            $reasons[] = 'disk_cap';
        } else {
            break;
        }
    }

    // Opportunistic: drop stale rate-limit buckets older than 2 hours.
    mra_prune_rate_limit_files();

    $bytesAfter = mra_chats_disk_bytes($domain);
    return [
        'deleted' => $deleted,
        'bytes_before' => $bytesBefore,
        'bytes_after' => $bytesAfter,
        'reason' => array_values(array_unique($reasons)),
    ];
}

/**
 * Remove rate-limit JSON older than ~2 hours.
 */
function mra_prune_rate_limit_files()
{
    $dir = MRA_ROOT . '/data/rate-limit';
    if (!is_dir($dir)) {
        return;
    }
    $cutoff = mra_now_unix() - 7200;
    $dh = @opendir($dir);
    if ($dh === false) {
        return;
    }
    while (($name = readdir($dh)) !== false) {
        if ($name === '.' || $name === '..' || substr($name, -5) !== '.json') {
            continue;
        }
        $path = $dir . '/' . $name;
        if (is_file($path) && (int) @filemtime($path) < $cutoff) {
            @unlink($path);
        }
    }
    closedir($dh);
}

/**
 * Acquire a concurrent-request slot (1 or 2). Returns [ok, error, handle|null].
 *
 * @return array{0:bool,1:string,2:resource|null}
 */
function mra_concurrent_acquire(array $cfg, $username)
{
    $max = max(1, min(2, (int) ($cfg['concurrent_requests'] ?? 2)));
    $user = preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim((string) $username))) ?: 'anon';
    $dir = mra_locks_dir($cfg['domain'] ?? null);
    for ($slot = 0; $slot < $max; $slot++) {
        $path = $dir . '/req_' . $user . '_' . $slot . '.lock';
        $fh = @fopen($path, 'c+');
        if ($fh === false) {
            continue;
        }
        if (@flock($fh, LOCK_EX | LOCK_NB)) {
            @ftruncate($fh, 0);
            @fwrite($fh, (string) getmypid() . ' ' . mra_now_unix());
            @fflush($fh);
            @chmod($path, 0600);
            return [true, '', $fh];
        }
        @fclose($fh);
    }
    return [false, 'Too many concurrent Mr Agent requests (max ' . $max . '). Try again shortly.', null];
}

/**
 * @param resource|null $handle
 */
function mra_concurrent_release($handle)
{
    if (is_resource($handle)) {
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }
}

/**
 * Reject oversized HTTP request bodies before JSON parse.
 *
 * @return array{0:bool,1:string}
 */
function mra_check_upload_size(array $cfg)
{
    $max = (int) ($cfg['max_upload_bytes'] ?? 262144);
    $cl = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($cl > $max) {
        return [false, 'Request body too large (max ' . $max . ' bytes).'];
    }
    return [true, ''];
}
