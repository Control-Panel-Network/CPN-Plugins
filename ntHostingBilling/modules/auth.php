<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_session_start()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('nthb_session');
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ]);
    }
}

/**
 * @param array<string,mixed> $cfg
 */
function nthb_admin_login($password, array $cfg)
{
    nthb_session_start();
    $expect = (string) ($cfg['admin_password'] ?? '');
    if ($expect === '' || $expect === 'CHANGE_ME') {
        return [false, 'Set admin_password in config.php before using the admin UI'];
    }
    if (!hash_equals($expect, (string) $password)) {
        return [false, 'Invalid admin password'];
    }
    session_regenerate_id(true);
    $_SESSION['nthb_admin'] = true;
    $_SESSION['nthb_admin_at'] = time();
    return [true, 'Signed in'];
}

function nthb_admin_logout()
{
    nthb_session_start();
    unset($_SESSION['nthb_admin'], $_SESSION['nthb_admin_at'], $_SESSION['nthb_client_id']);
}

function nthb_is_admin()
{
    nthb_session_start();
    return !empty($_SESSION['nthb_admin']);
}

function nthb_require_admin()
{
    if (!nthb_is_admin()) {
        nthb_redirect('?view=login');
    }
}

/**
 * Client portal login.
 */
function nthb_client_login(PDO $pdo, $email, $password)
{
    nthb_session_start();
    $email = strtolower(trim((string) $email));
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row) {
        return [false, 'Account not found'];
    }
    if (($row['status'] ?? '') === 'suspended') {
        return [false, 'Account suspended'];
    }
    $hash = (string) ($row['password_hash'] ?? '');
    if ($hash === '' || !password_verify((string) $password, $hash)) {
        return [false, 'Invalid email or password'];
    }
    session_regenerate_id(true);
    $_SESSION['nthb_client_id'] = (int) $row['id'];
    return [true, 'Signed in'];
}

function nthb_client_id()
{
    nthb_session_start();
    return isset($_SESSION['nthb_client_id']) ? (int) $_SESSION['nthb_client_id'] : 0;
}

function nthb_require_client()
{
    if (nthb_client_id() < 1) {
        nthb_redirect('?view=client-login');
    }
}
