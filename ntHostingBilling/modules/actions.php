<?php
if (!defined('NTHB_INIT')) {
    exit;
}

/**
 * @param array<string,mixed> $cfg
 * @param array<string,mixed> $license
 * @return array{notice?:string,error?:string,redirect?:string,view?:string}
 */
function nthb_handle_action(PDO $pdo, array $cfg, $action, array $license)
{
    $action = (string) $action;
    switch ($action) {
        case 'admin_login':
            list($ok, $msg) = nthb_admin_login(nthb_post('password'), $cfg);
            return $ok ? ['notice' => $msg, 'redirect' => '?view=home'] : ['error' => $msg, 'view' => 'login'];

        case 'admin_logout':
            nthb_admin_logout();
            return ['redirect' => '?view=login'];

        case 'client_login':
            list($ok, $msg) = nthb_client_login($pdo, nthb_post('email'), nthb_post('password'));
            return $ok ? ['notice' => $msg, 'redirect' => '?view=portal'] : ['error' => $msg, 'view' => 'client-login'];

        case 'client_logout':
            nthb_session_start();
            unset($_SESSION['nthb_client_id']);
            return ['redirect' => '?view=client-login'];

        case 'recovery_request':
            $base = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http')
                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            list($ok, $msg) = nthb_recovery_request($pdo, nthb_post('email'), $cfg, $base);
            return $ok ? ['notice' => $msg, 'view' => 'recovery'] : ['error' => $msg, 'view' => 'recovery'];

        case 'recovery_reset':
            list($ok, $msg) = nthb_recovery_reset($pdo, nthb_post('token'), nthb_post('password'));
            return $ok ? ['notice' => $msg, 'redirect' => '?view=client-login'] : ['error' => $msg, 'view' => 'recovery-reset'];

        case 'save_license':
            nthb_require_admin();
            return nthb_action_save_license($cfg);

        case 'client_create':
            nthb_require_admin();
            if (empty($license['ok'])) {
                return ['error' => 'Active license required', 'view' => 'license'];
            }
            list($ok, $res) = nthb_client_create($pdo, [
                'email' => nthb_post('email'),
                'display_name' => nthb_post('display_name'),
                'cpn_username' => nthb_post('cpn_username'),
                'password' => nthb_post('password'),
            ]);
            return $ok ? ['notice' => 'Client #' . $res . ' created', 'redirect' => '?view=clients'] : ['error' => (string) $res, 'view' => 'clients'];

        case 'client_status':
            nthb_require_admin();
            list($ok, $msg) = nthb_client_update_status($pdo, (int) nthb_post('id'), nthb_post('status'));
            return $ok ? ['notice' => $msg, 'redirect' => '?view=clients'] : ['error' => $msg, 'view' => 'clients'];

        case 'product_create':
            nthb_require_admin();
            if (empty($license['ok'])) {
                return ['error' => 'Active license required', 'view' => 'license'];
            }
            list($ok, $res) = nthb_product_create($pdo, $_POST);
            return $ok ? ['notice' => 'Product #' . $res . ' created', 'redirect' => '?view=products'] : ['error' => (string) $res, 'view' => 'products'];

        case 'order_create':
            nthb_require_admin();
            if (empty($license['ok'])) {
                return ['error' => 'Active license required', 'view' => 'license'];
            }
            list($ok, $res) = nthb_order_create($pdo, (int) nthb_post('client_id'), (int) nthb_post('product_id'), nthb_post('notes'));
            return $ok ? ['notice' => 'Order #' . $res . ' created', 'redirect' => '?view=orders'] : ['error' => (string) $res, 'view' => 'orders'];

        case 'invoice_mark_paid':
            nthb_require_admin();
            if (empty($license['ok'])) {
                return ['error' => 'Active license required', 'view' => 'license'];
            }
            list($ok, $msg) = nthb_invoice_mark_paid($pdo, (int) nthb_post('id'), nthb_post('payment_method', 'manual'), nthb_post('payment_ref'));
            return $ok ? ['notice' => $msg, 'redirect' => '?view=invoices'] : ['error' => $msg, 'view' => 'invoices'];

        case 'subscription_status':
            nthb_require_admin();
            list($ok, $msg) = nthb_subscription_set_status($pdo, (int) nthb_post('id'), nthb_post('status'));
            return $ok ? ['notice' => $msg, 'redirect' => '?view=subscriptions'] : ['error' => $msg, 'view' => 'subscriptions'];

        case 'ownership_assign':
            nthb_require_admin();
            list($ok, $msg) = nthb_ownership_assign($pdo, (int) nthb_post('client_id'), nthb_post('domain'), nthb_post('role', 'owner'));
            return $ok ? ['notice' => $msg, 'redirect' => '?view=ownership'] : ['error' => $msg, 'view' => 'ownership'];

        case 'ownership_remove':
            nthb_require_admin();
            list($ok, $msg) = nthb_ownership_remove($pdo, (int) nthb_post('id'));
            return $ok ? ['notice' => $msg, 'redirect' => '?view=ownership'] : ['error' => $msg, 'view' => 'ownership'];
    }

    return ['error' => 'Unknown action'];
}

/**
 * @param array<string,mixed> $cfg
 * @return array{notice?:string,error?:string,redirect?:string}
 */
function nthb_action_save_license(array $cfg)
{
    $path = NTHB_ROOT . '/config.php';
    $current = is_file($path) ? (require $path) : [];
    if (!is_array($current)) {
        $current = [];
    }
    $current['license_email'] = nthb_post('license_email');
    $current['activation_key'] = nthb_post('activation_key');
    $current['entitlement_token'] = nthb_post('entitlement_token');
    $current['api_base'] = nthb_post('api_base', 'https://api.newstargeted.com');
    $current['paypal_me_url'] = nthb_post('paypal_me_url');
    $current['paypal_payment_link'] = nthb_post('paypal_payment_link');
    if (nthb_post('admin_password') !== '') {
        $current['admin_password'] = nthb_post('admin_password');
    } elseif (empty($current['admin_password'])) {
        $current['admin_password'] = (string) ($cfg['admin_password'] ?? '');
    }
    $export = "<?php\nreturn " . var_export($current, true) . ";\n";
    if (@file_put_contents($path, $export) === false) {
        return ['error' => 'Could not write config.php (check permissions)', 'view' => 'license'];
    }
    @chmod($path, 0600);
    return ['notice' => 'License settings saved', 'redirect' => '?view=license'];
}
