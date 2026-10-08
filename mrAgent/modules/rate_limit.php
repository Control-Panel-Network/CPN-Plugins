<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Simple file-based hourly rate limit per user (+ IP).
 */
function mra_rate_limit_check(array $cfg, $username)
{
    $limit = max(1, (int) ($cfg['rate_limit_per_hour'] ?? 60));
    $username = strtolower(trim((string) $username));
    $ip = mra_client_ip();
    $bucket = gmdate('YmdH');
    $dir = MRA_ROOT . '/data/rate-limit';
    mra_safe_mkdir($dir, 0700);
    $key = hash('sha256', $username . '|' . $ip . '|' . $bucket);
    $path = $dir . '/' . $key . '.json';
    $count = 0;
    if (is_file($path)) {
        $raw = @file_get_contents($path);
        $data = json_decode((string) $raw, true);
        if (is_array($data) && isset($data['count'])) {
            $count = (int) $data['count'];
        }
    }
    if ($count >= $limit) {
        return [false, 'Rate limit reached (' . $limit . ' messages/hour). Try again later.'];
    }
    $count++;
    @file_put_contents($path, json_encode(['count' => $count, 'bucket' => $bucket], JSON_UNESCAPED_SLASHES), LOCK_EX);
    @chmod($path, 0600);
    return [true, ''];
}
