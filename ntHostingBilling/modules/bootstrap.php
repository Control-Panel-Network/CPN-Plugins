<?php
/**
 * ntHostingBilling bootstrap.
 */
if (!defined('NTHB_INIT')) {
    define('NTHB_INIT', true);
}

define('NTHB_ROOT', dirname(__DIR__));
define('NTHB_VERSION', '1.0.0');
define('NTHB_PLUGIN_ID', 'ntHostingBilling');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/config_loader.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/entitlement.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/clients.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/invoices.php';
require_once __DIR__ . '/subscriptions.php';
require_once __DIR__ . '/ownership.php';
require_once __DIR__ . '/recovery.php';
require_once __DIR__ . '/billing.php';
require_once __DIR__ . '/audit.php';

/**
 * @return PDO
 */
function nthb_boot()
{
    $cfg = nthb_config();
    $pdo = nthb_db($cfg);
    nthb_migrate($pdo);
    return $pdo;
}
