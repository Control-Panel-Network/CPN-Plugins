<?php
/**
 * ntHostingBilling public entry (site docroot /nt-billing).
 */
define('NTHB_INIT', true);

require_once dirname(__DIR__) . '/modules/bootstrap.php';

nthb_session_start();

try {
    $pdo = nthb_boot();
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Hosting Commerce failed to start. Check data directory permissions.';
    exit;
}

$cfg = nthb_config_with_runtime_token(nthb_config());
$view = nthb_get('view', 'home');
$notice = '';
$error = '';
$license = nthb_check_entitlement($cfg);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = nthb_post('action');
    if (!nthb_csrf_check() && $action !== '') {
        $error = 'Invalid CSRF token';
    } else {
        require dirname(__DIR__) . '/modules/actions.php';
        $result = nthb_handle_action($pdo, $cfg, $action, $license);
        $notice = $result['notice'] ?? '';
        $error = $result['error'] ?? '';
        if (!empty($result['redirect'])) {
            nthb_redirect($result['redirect']);
        }
        if (!empty($result['view'])) {
            $view = (string) $result['view'];
        }
        $cfg = nthb_config_with_runtime_token(nthb_config());
        $license = nthb_check_entitlement($cfg);
    }
}

$locked = empty($license['ok']) && !in_array($view, ['login', 'license', 'client-login', 'recovery', 'recovery-reset'], true);
if ($locked && nthb_is_admin() && $view !== 'license') {
    // Admins may open license page; other admin views stay locked until entitled.
    if (!in_array($view, ['home', 'license'], true)) {
        $view = 'license';
        $error = $error !== '' ? $error : (string) ($license['message'] ?? 'License required');
    }
}

require dirname(__DIR__) . '/views/layout.php';
