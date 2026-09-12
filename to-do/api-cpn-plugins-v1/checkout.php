<?php
/**
 * Checkout / purchase hints for a paid CPN plugin.
 * GET|POST /api/cpn-plugins/v1/checkout.php?plugin=ntHostingBilling
 * Secrets never returned; PayPal links are public operator URLs only.
 */
if (!defined('API_APP_INIT')) {
    define('API_APP_INIT', true);
}

$configPath = dirname(__DIR__, 3) . '/config.php';
if (is_file($configPath)) {
    require_once $configPath;
}

header('Content-Type: application/json; charset=utf-8');
header('Vary: Origin');
if (!empty($_SERVER['HTTP_ORIGIN']) && function_exists('getCorsOrigins')) {
    $allowedOrigins = getCorsOrigins();
    if (is_array($allowedOrigins) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }
}

$plugin = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode((string) $raw, true);
    if (is_array($data)) {
        $plugin = trim((string) ($data['plugin'] ?? $data['plugin_name'] ?? ''));
    }
}
if ($plugin === '') {
    $plugin = trim((string) ($_GET['plugin'] ?? $_GET['plugin_name'] ?? ''));
}

$allowedPath = dirname(__DIR__, 3) . '/modules/plugin_grants/allowed_plugins.json';
$allowed = [];
if (is_file($allowedPath)) {
    $decoded = json_decode((string) file_get_contents($allowedPath), true);
    if (is_array($decoded)) {
        $allowed = $decoded;
    }
}

if ($plugin === '' || !isset($allowed[$plugin])) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Unknown or unpaid plugin id',
        'plugin' => $plugin,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$paypalMe = 'https://paypal.me/KimBS?locale.x=en_US&country.x=NO';
if (defined('CPN_PLUGIN_PAYPAL_ME') && is_string(CPN_PLUGIN_PAYPAL_ME) && CPN_PLUGIN_PAYPAL_ME !== '') {
    $paypalMe = CPN_PLUGIN_PAYPAL_ME;
}

$out = [
    'success' => true,
    'plugin' => $plugin,
    'name' => $allowed[$plugin],
    'pricing' => 'paid',
    'currency' => 'USD',
    'message' => 'Purchase or request a Shop Grant / activation key for this plugin. After payment, operators activate via activate-plugin-key or admin plugin grants.',
    'paypal_me_url' => $paypalMe,
    'verify_endpoints' => [
        'grant' => 'https://api.newstargeted.com/api/verify-plugin-grant.php',
        'activation_key' => 'https://api.newstargeted.com/api/activate-plugin-key.php',
        'entitlement' => 'https://api.newstargeted.com/api/verify-entitlement.php',
        'paypal' => 'https://api.newstargeted.com/api/verify-paypal-payment.php',
    ],
    'shop_grants_admin' => 'https://api.newstargeted.com/admin/plugin-grants.php',
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
