<?php
if (!defined('NTHB_INIT')) {
    exit;
}

/**
 * Load secrets from config.php (plugin dir, then /var/lib/cpn/...).
 *
 * @return array<string,mixed>
 */
function nthb_config()
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }

    $candidates = [
        NTHB_ROOT . '/config.php',
        '/var/lib/cpn/nt-hosting-billing/config.php',
    ];
    $loaded = [];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            $data = require $path;
            if (is_array($data)) {
                $loaded = array_merge($loaded, $data);
            }
        }
    }

    $defaults = [
        'api_base' => 'https://api.newstargeted.com',
        'entitlement_token' => '',
        'activation_key' => '',
        'license_email' => '',
        'server_fingerprint' => '',
        'paypal_me_url' => '',
        'paypal_payment_link' => '',
        'mail_from' => 'noreply@localhost',
        'mail_from_name' => 'News Targeted Hosting',
        'admin_password' => '',
        'sqlite_path' => '',
    ];
    $cfg = array_merge($defaults, $loaded);
    return $cfg;
}

function nthb_data_dir()
{
    $dir = NTHB_ROOT . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}
