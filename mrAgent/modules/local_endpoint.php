<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Local OpenAI-compatible endpoint helpers (Ollama, LM Studio, Bionic, etc.).
 * Panel/PHP calls the endpoint on the CPN server (loopback or owner-allowed LAN).
 */

/**
 * @return array{ok:bool,error?:string,host?:string,scheme?:string,port?:int,path?:string}
 */
function mra_parse_local_base_url($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return ['ok' => false, 'error' => 'Local base URL is empty'];
    }
    if (!preg_match('#^https?://#i', $url)) {
        return ['ok' => false, 'error' => 'Local base URL must start with http:// or https://'];
    }
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return ['ok' => false, 'error' => 'Could not parse local base URL'];
    }
    $host = strtolower((string) $parts['host']);
    $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
    $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
    $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';
    if ($path === '') {
        $path = '/v1';
    }
    return [
        'ok' => true,
        'host' => $host,
        'scheme' => $scheme,
        'port' => $port,
        'path' => $path,
    ];
}

function mra_host_is_loopback($host)
{
    $host = strtolower(trim((string) $host));
    return $host === '127.0.0.1' || $host === 'localhost' || $host === '::1';
}

function mra_host_is_private_lan($host)
{
    $host = strtolower(trim((string) $host));
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return (bool) preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/', $host);
    }
    return false;
}

/**
 * @param array<string,mixed> $cfg
 */
function mra_local_base_allowed($url, array $cfg)
{
    $parsed = mra_parse_local_base_url($url);
    if (empty($parsed['ok'])) {
        return false;
    }
    $host = (string) $parsed['host'];
    if (mra_host_is_loopback($host)) {
        return true;
    }
    return !empty($cfg['local_allow_lan']) && mra_host_is_private_lan($host);
}

function mra_normalize_local_base_url($url)
{
    $parsed = mra_parse_local_base_url($url);
    if (empty($parsed['ok'])) {
        return '';
    }
    $port = (int) $parsed['port'];
    $scheme = (string) $parsed['scheme'];
    $defaultPort = ($scheme === 'https') ? 443 : 80;
    $host = (string) $parsed['host'];
    $authority = $host;
    if ($port > 0 && $port !== $defaultPort) {
        $authority .= ':' . $port;
    }
    $path = (string) $parsed['path'];
    if ($path === '' || $path === '/') {
        $path = '/v1';
    } elseif (substr($path, -3) !== '/v1' && strpos($path, '/v1') === false) {
        $path = rtrim($path, '/') . '/v1';
    }
    return $scheme . '://' . $authority . $path;
}

/**
 * @param array<string,mixed> $cfg
 * @return array{base:string,model:string,api_key:string,allow_private:bool}
 */
function mra_local_endpoint_cfg(array $cfg)
{
    $base = mra_normalize_local_base_url((string) ($cfg['local_base_url'] ?? 'http://127.0.0.1:11434/v1'));
    if ($base === '') {
        $base = 'http://127.0.0.1:11434/v1';
    }
    $model = trim((string) ($cfg['local_model'] ?? 'llama3.2:1b'));
    if ($model === '') {
        $model = 'llama3.2:1b';
    }
    $key = (string) ($cfg['local_api_key'] ?? '');
    if ($key === '' && function_exists('mra_resolve_api_key') && function_exists('mra_user')) {
        $key = mra_resolve_api_key('local', mra_user(), $cfg);
    }
    return [
        'base' => $base,
        'model' => $model,
        'api_key' => $key,
        'allow_private' => mra_local_base_allowed($base, $cfg),
    ];
}

/**
 * @param array<int,array<string,mixed>> $messages
 * @param array<string,mixed> $cfg
 * @return array{ok:bool,reply?:string,error?:string,provider?:string,model?:string}
 */
function mra_local_chat_messages(array $messages, array $cfg, $model = '', $timeout = 0)
{
    $ep = mra_local_endpoint_cfg($cfg);
    if (empty($ep['allow_private'])) {
        return [
            'ok' => false,
            'error' => 'Local base URL is not allowed. Use loopback (127.0.0.1 / localhost) or enable Local allow LAN.',
        ];
    }
    if ($model === '' || $model === 'cpn-help') {
        $model = $ep['model'];
    }
    if ($timeout <= 0) {
        $timeout = (int) ($cfg['local_timeout_seconds'] ?? 45);
    }
    $maxTokens = (int) ($cfg['max_tokens_per_reply'] ?? 1024);
    $maxBytes = (int) ($cfg['local_max_response_bytes'] ?? 1048576);
    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => max(64, min(8192, $maxTokens)),
    ];
    $headers = ['Content-Type: application/json'];
    if ($ep['api_key'] !== '') {
        $headers[] = 'Authorization: Bearer ' . $ep['api_key'];
    }
    $host = (string) parse_url($ep['base'], PHP_URL_HOST);
    $allowPrivate = !mra_host_is_loopback($host);
    $resp = mra_http_json(
        'POST',
        $ep['base'] . '/chat/completions',
        $payload,
        $headers,
        max(5, (int) $timeout),
        $allowPrivate,
        $maxBytes
    );
    if (empty($resp['ok'])) {
        return [
            'ok' => false,
            'error' => isset($resp['error']) ? (string) $resp['error'] : 'Local model request failed',
        ];
    }
    $body = is_array($resp['body']) ? $resp['body'] : [];
    $text = '';
    if (isset($body['choices'][0]['message']['content'])) {
        $text = trim((string) $body['choices'][0]['message']['content']);
    }
    if ($text === '') {
        return ['ok' => false, 'error' => 'Empty local model reply'];
    }
    return [
        'ok' => true,
        'reply' => $text,
        'provider' => 'local',
        'model' => $model,
    ];
}
