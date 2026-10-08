<?php
/**
 * Mr Agent public entry (site docroot /mr-agent).
 */
define('MRA_INIT', true);

require_once dirname(__DIR__) . '/modules/bootstrap.php';

mra_session_start();

try {
    $cfg = mra_boot();
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Mr Agent failed to start. Check data directory permissions.';
    mra_log('boot_failed', ['error' => mra_redact($e->getMessage())]);
    exit;
}

$api = mra_get('api');
if ($api !== '') {
    mra_api_dispatch($api, $cfg);
}

$view = mra_get('view', 'chat');
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && mra_post('action') !== '') {
    if (!mra_csrf_check()) {
        $error = 'Invalid CSRF token';
    } else {
        $action = mra_post('action');
        if ($action === 'login') {
            list($ok, $msg) = mra_login(mra_post('username'), mra_post('password'), $cfg);
            if ($ok) {
                if (mra_post('package_id') !== '') {
                    $_SESSION['mra_package'] = strtolower(mra_post('package_id'));
                }
                mra_redirect('?view=chat');
            }
            $error = $msg;
            $view = 'login';
        } elseif ($action === 'logout') {
            mra_logout();
            mra_redirect('?view=login');
        }
    }
}

if (mra_user() === '' && $view !== 'login') {
    $view = 'login';
}

if (mra_user() !== '' && $view === 'chat') {
    list($aclOk, $aclErr) = mra_acl_allows($cfg);
    if (!$aclOk) {
        $error = $aclErr;
        $view = 'denied';
    }
}

$allowedViews = ['login', 'chat', 'settings', 'providers', 'denied', 'about'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'chat';
}

require dirname(__DIR__) . '/views/layout.php';
