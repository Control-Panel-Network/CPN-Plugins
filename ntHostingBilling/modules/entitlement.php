<?php
if (!defined('NTHB_INIT')) {
    exit;
}

/**
 * Call api.newstargeted.com entitlement endpoints (JSON POST).
 *
 * @param string $path
 * @param array<string,mixed> $payload
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function nthb_api_post($path, array $payload, array $cfg)
{
    $base = rtrim((string) ($cfg['api_base'] ?? 'https://api.newstargeted.com'), '/');
    $url = $base . $path;
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return ['success' => false, 'has_access' => false, 'message' => 'Could not encode payload'];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ntHostingBilling/' . NTHB_VERSION,
                'X-Plugin-Name: ' . NTHB_PLUGIN_ID,
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            return ['success' => false, 'has_access' => false, 'message' => 'API request failed', 'error' => $err];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'has_access' => false, 'message' => 'Invalid API JSON', 'http' => $code];
        }
        $decoded['_http'] = $code;
        return $decoded;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\nUser-Agent: ntHostingBilling/" . NTHB_VERSION . "\r\n",
            'content' => $body,
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return ['success' => false, 'has_access' => false, 'message' => 'API request failed'];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : ['success' => false, 'has_access' => false, 'message' => 'Invalid API JSON'];
}

/**
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function nthb_check_entitlement(array $cfg)
{
    $email = trim((string) ($cfg['license_email'] ?? ''));
    $domain = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
    $fp = trim((string) ($cfg['server_fingerprint'] ?? ''));
    if ($fp === '') {
        $fp = hash('sha256', php_uname('n') . '|' . NTHB_PLUGIN_ID);
    }

    $token = trim((string) ($cfg['entitlement_token'] ?? ''));
    if ($token !== '') {
        $resp = nthb_api_post('/api/verify-entitlement.php', [
            'entitlement_token' => $token,
            'plugin_name' => NTHB_PLUGIN_ID,
            'user_email' => $email,
            'server_fingerprint' => $fp,
            'domain' => $domain,
        ], $cfg);
        if (!empty($resp['success']) && !empty($resp['has_access'])) {
            if (!empty($resp['entitlement_token']) && is_string($resp['entitlement_token'])) {
                nthb_persist_entitlement_token($resp['entitlement_token']);
            }
            return ['ok' => true, 'via' => 'entitlement', 'message' => (string) ($resp['message'] ?? 'Entitled')];
        }
    }

    $key = trim((string) ($cfg['activation_key'] ?? ''));
    if ($key !== '') {
        $resp = nthb_api_post('/api/activate-plugin-key.php', [
            'activation_key' => $key,
            'plugin_name' => NTHB_PLUGIN_ID,
            'user_email' => $email,
            'server_fingerprint' => $fp,
            'domain' => $domain,
        ], $cfg);
        if (!empty($resp['success']) && !empty($resp['has_access'])) {
            if (!empty($resp['entitlement_token']) && is_string($resp['entitlement_token'])) {
                nthb_persist_entitlement_token($resp['entitlement_token']);
            }
            return ['ok' => true, 'via' => 'activation_key', 'message' => (string) ($resp['message'] ?? 'Activated')];
        }
    }

    if ($email !== '') {
        $resp = nthb_api_post('/api/verify-plugin-grant.php', [
            'plugin_name' => NTHB_PLUGIN_ID,
            'plugin_version' => NTHB_VERSION,
            'user_email' => $email,
            'server_fingerprint' => $fp,
            'domain' => $domain,
            'user_ip' => isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
            'timestamp' => time(),
        ], $cfg);
        if (!empty($resp['success']) && !empty($resp['has_access'])) {
            if (!empty($resp['entitlement_token']) && is_string($resp['entitlement_token'])) {
                nthb_persist_entitlement_token($resp['entitlement_token']);
            }
            return ['ok' => true, 'via' => 'plugin_grant', 'message' => (string) ($resp['message'] ?? 'Granted')];
        }
        return [
            'ok' => false,
            'via' => 'none',
            'message' => (string) ($resp['message'] ?? 'No active license for ntHostingBilling'),
        ];
    }

    return [
        'ok' => false,
        'via' => 'none',
        'message' => 'Configure license_email and activation_key or entitlement_token in config.php',
    ];
}

function nthb_persist_entitlement_token($token)
{
    $token = trim((string) $token);
    if ($token === '') {
        return;
    }
    $path = nthb_data_dir() . '/entitlement.local.json';
    $payload = [
        'entitlement_token' => $token,
        'updated_at' => nthb_now(),
    ];
    @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    @chmod($path, 0600);
}

/**
 * Merge runtime entitlement token from data dir into config view.
 *
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function nthb_config_with_runtime_token(array $cfg)
{
    $path = nthb_data_dir() . '/entitlement.local.json';
    if (!is_file($path)) {
        return $cfg;
    }
    $raw = @file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (is_array($data) && !empty($data['entitlement_token'])) {
        $cfg['entitlement_token'] = (string) $data['entitlement_token'];
    }
    return $cfg;
}
