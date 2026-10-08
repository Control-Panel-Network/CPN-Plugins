<?php
if (!defined('MRA_INIT')) {
    exit;
}

function mra_session_start()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('mra_session');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $secure,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function mra_csrf_token()
{
    mra_session_start();
    if (empty($_SESSION['mra_csrf'])) {
        $_SESSION['mra_csrf'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['mra_csrf'];
}

function mra_csrf_check($token = null)
{
    mra_session_start();
    if ($token === null) {
        $token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
        if ($token === '') {
            $hdr = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? (string) $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
            $token = $hdr;
        }
    }
    $expect = isset($_SESSION['mra_csrf']) ? (string) $_SESSION['mra_csrf'] : '';
    return $expect !== '' && $token !== '' && hash_equals($expect, (string) $token);
}

/**
 * Login with username + access password (owner-set gate).
 * Username is used for per-user keys and rate limits; role is owner when password matches owner gate
 * and username is "owner" or "admin", else user.
 */
function mra_login($username, $password, array $cfg)
{
    mra_session_start();
    $username = strtolower(trim((string) $username));
    $password = (string) $password;
    if ($username === '' || !preg_match('/^[a-z0-9_.\-]{1,64}$/', $username)) {
        return [false, 'Enter a valid username (letters, numbers, . _ -).'];
    }
    $expect = (string) ($cfg['access_password'] ?? '');
    if ($expect === '' || $expect === 'CHANGE_ME') {
        return [false, 'Owner must set access_password in config before chat can be used.'];
    }
    if (!hash_equals($expect, $password)) {
        mra_log('login_failed', ['user' => $username, 'ip' => mra_client_ip()]);
        return [false, 'Invalid access password.'];
    }
    session_regenerate_id(true);
    $role = in_array($username, ['owner', 'admin', 'cpnowner'], true) ? 'owner' : 'user';
    $_SESSION['mra_user'] = $username;
    $_SESSION['mra_role'] = $role;
    $_SESSION['mra_at'] = mra_now_unix();
    return [true, 'Signed in'];
}

function mra_logout()
{
    mra_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], !empty($p['secure']), !empty($p['httponly']));
    }
    session_destroy();
}

function mra_user()
{
    mra_session_start();
    return isset($_SESSION['mra_user']) ? (string) $_SESSION['mra_user'] : '';
}

function mra_role()
{
    mra_session_start();
    return isset($_SESSION['mra_role']) ? (string) $_SESSION['mra_role'] : '';
}

function mra_is_owner()
{
    return mra_role() === 'owner';
}

function mra_require_login()
{
    if (mra_user() === '') {
        mra_redirect('?view=login');
    }
}

function mra_require_owner()
{
    mra_require_login();
    if (!mra_is_owner()) {
        mra_json(['ok' => false, 'error' => 'Owner access required'], 403);
    }
}
