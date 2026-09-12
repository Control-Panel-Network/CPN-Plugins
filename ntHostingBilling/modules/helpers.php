<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_now()
{
    return gmdate('Y-m-d H:i:s');
}

function nthb_json($data, $code = 200)
{
    http_response_code((int) $code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function nthb_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function nthb_post($key, $default = '')
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function nthb_get($key, $default = '')
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

function nthb_money_cents($cents, $currency = 'USD')
{
    $cents = (int) $cents;
    $sign = $cents < 0 ? '-' : '';
    $cents = abs($cents);
    return $sign . strtoupper((string) $currency) . ' ' . number_format($cents / 100, 2, '.', '');
}

function nthb_csrf_token()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['nthb_csrf'])) {
        $_SESSION['nthb_csrf'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['nthb_csrf'];
}

function nthb_csrf_check()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    $expect = isset($_SESSION['nthb_csrf']) ? (string) $_SESSION['nthb_csrf'] : '';
    if ($expect === '' || $token === '' || !hash_equals($expect, $token)) {
        return false;
    }
    return true;
}

function nthb_redirect($path)
{
    header('Location: ' . $path, true, 302);
    exit;
}

function nthb_status_allowed($value, array $allowed)
{
    $value = strtolower(trim((string) $value));
    return in_array($value, $allowed, true) ? $value : null;
}
