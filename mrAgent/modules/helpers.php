<?php
if (!defined('MRA_INIT')) {
    exit;
}

function mra_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mra_json($data, $code = 200)
{
    http_response_code((int) $code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function mra_post($key, $default = '')
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function mra_get($key, $default = '')
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

function mra_body_json()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function mra_redirect($path)
{
    header('Location: ' . $path, true, 302);
    exit;
}

function mra_now_unix()
{
    return time();
}

function mra_client_ip()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    return preg_replace('/[^0-9a-fA-F:\.]/', '', $ip) ?: '0.0.0.0';
}

/**
 * Redact secret-looking values for logs and error messages.
 */
function mra_redact($text)
{
    $text = (string) $text;
    $text = preg_replace('/(sk-[A-Za-z0-9_\-]{8,})/', 'sk-REDACTED', $text);
    $text = preg_replace('/(sk-ant-[A-Za-z0-9_\-]{8,})/', 'sk-ant-REDACTED', $text);
    $text = preg_replace('/(Bearer\s+)[A-Za-z0-9_\-\.]+/i', '$1REDACTED', $text);
    $text = preg_replace('/("?(?:api_key|apiKey|token|password|secret)"?\s*[:=]\s*")[^"]+"/i', '$1***"', $text);
    return $text;
}

function mra_log($message, $context = [])
{
    $dir = MRA_ROOT . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $line = gmdate('Y-m-d\TH:i:s\Z') . ' ' . mra_redact((string) $message);
    if (!empty($context)) {
        $line .= ' ' . mra_redact(json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    @file_put_contents($dir . '/mr-agent.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    @chmod($dir . '/mr-agent.log', 0600);
}

function mra_safe_mkdir($path, $mode = 0700)
{
    if (!is_dir($path)) {
        if (!@mkdir($path, $mode, true) && !is_dir($path)) {
            return false;
        }
    }
    @chmod($path, $mode);
    return true;
}
