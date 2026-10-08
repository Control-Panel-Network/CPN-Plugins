<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * JSON HTTP helper (curl). Does not log Authorization headers or bodies with keys.
 *
 * @param array<string,mixed>|null $payload
 * @param array<int,string> $headers
 * @param bool $allowPrivate When true, allow RFC1918 hosts (local LLM on LAN only).
 * @param int $maxBytes Cap request JSON and response body size (0 = default 8 MiB).
 * @return array{ok:bool,status:int,body:mixed,error?:string}
 */
function mra_http_json($method, $url, array $payload = null, array $headers = [], $timeout = 45, $allowPrivate = false, $maxBytes = 0)
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'status' => 0, 'body' => null, 'error' => 'php-curl is required'];
    }
    $url = (string) $url;
    if (!preg_match('#^https?://#i', $url)) {
        return ['ok' => false, 'status' => 0, 'body' => null, 'error' => 'Invalid URL scheme'];
    }
    $maxBytes = (int) $maxBytes;
    if ($maxBytes <= 0) {
        $maxBytes = 8388608; // 8 MiB safety default
    }
    // Block metadata targets. Loopback always OK. Private LAN only when allowPrivate.
    $host = parse_url($url, PHP_URL_HOST);
    $host = strtolower((string) $host);
    $isLoopback = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    if ($host === '169.254.169.254' || $host === 'metadata.google.internal') {
        return ['ok' => false, 'status' => 0, 'body' => null, 'error' => 'Blocked host'];
    }
    $isPrivate = (bool) preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/', $host);
    if (!$isLoopback && $isPrivate && !$allowPrivate) {
        return ['ok' => false, 'status' => 0, 'body' => null, 'error' => 'Private hosts are not allowed for remote providers'];
    }
    if (!$isLoopback && !$isPrivate && $allowPrivate) {
        return ['ok' => false, 'status' => 0, 'body' => null, 'error' => 'allowPrivate is limited to loopback and LAN hosts'];
    }

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => min(10, (int) $timeout),
        CURLOPT_TIMEOUT => (int) $timeout,
        CURLOPT_CUSTOMREQUEST => strtoupper((string) $method),
        CURLOPT_HTTPHEADER => $headers,
    ];
    if ($payload !== null) {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['ok' => false, 'status' => 0, 'body' => null, 'error' => 'Could not encode request'];
        }
        if (strlen($json) > $maxBytes) {
            return ['ok' => false, 'status' => 0, 'body' => null, 'error' => 'Request payload too large (max ' . $maxBytes . ' bytes)'];
        }
        $opts[CURLOPT_POSTFIELDS] = $json;
        $hasCt = false;
        foreach ($headers as $h) {
            if (stripos($h, 'Content-Type:') === 0) {
                $hasCt = true;
                break;
            }
        }
        if (!$hasCt) {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno) {
        mra_log('http_error', ['status' => 0, 'error' => mra_redact($err)]);
        return ['ok' => false, 'status' => 0, 'body' => null, 'error' => 'Upstream request failed'];
    }
    if (strlen((string) $raw) > $maxBytes) {
        mra_log('http_response_too_large', ['bytes' => strlen((string) $raw), 'max' => $maxBytes]);
        return ['ok' => false, 'status' => $status, 'body' => null, 'error' => 'Upstream response too large (max ' . $maxBytes . ' bytes)'];
    }
    $body = json_decode((string) $raw, true);
    if (!is_array($body)) {
        $body = ['raw' => mra_redact(substr((string) $raw, 0, 500))];
    }
    if ($status < 200 || $status >= 300) {
        $msg = isset($body['error']['message']) ? (string) $body['error']['message'] : 'Provider HTTP ' . $status;
        mra_log('http_status', ['status' => $status, 'error' => mra_redact($msg)]);
        return ['ok' => false, 'status' => $status, 'body' => $body, 'error' => mra_redact($msg)];
    }
    return ['ok' => true, 'status' => $status, 'body' => $body];
}
