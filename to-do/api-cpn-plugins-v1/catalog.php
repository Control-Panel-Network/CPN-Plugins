<?php
/**
 * CPN paid plugins catalog (public metadata; no secrets).
 * GET /api/cpn-plugins/v1/catalog.php
 */
if (!defined('API_APP_INIT')) {
    define('API_APP_INIT', true);
}

$configPath = dirname(__DIR__, 3) . '/config.php';
if (is_file($configPath)) {
    require_once $configPath;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$allowedPath = dirname(__DIR__, 3) . '/modules/plugin_grants/allowed_plugins.json';
$allowed = [];
if (is_file($allowedPath)) {
    $decoded = json_decode((string) file_get_contents($allowedPath), true);
    if (is_array($decoded)) {
        $allowed = $decoded;
    }
}

$cpnPaid = [
    [
        'id' => 'ntHostingBilling',
        'name' => 'News Targeted Hosting Commerce',
        'pricing' => 'paid',
        'category' => 'Billing',
        'version' => '1.0.0',
        'description' => 'Hosting business layer for CPN: clients, packages, orders, invoices, subscriptions, ownership, billing, recovery.',
        'catalog_repo' => 'Control-Panel-Network/CPN-Plugins',
        'checkout' => '/api/cpn-plugins/v1/checkout.php?plugin=ntHostingBilling',
        'entitlement_endpoints' => [
            '/api/verify-plugin-grant.php',
            '/api/activate-plugin-key.php',
            '/api/verify-entitlement.php',
        ],
    ],
    [
        'id' => 'ntMalwareApi',
        'name' => 'News Targeted Malware API',
        'pricing' => 'paid',
        'category' => 'Security',
        'version' => '1.0.0',
        'description' => 'Paid malware status via api.newstargeted.com; token in /var/lib/cpn/malware.json.',
        'catalog_repo' => 'Control-Panel-Network/CPN-Plugins',
        'checkout' => '/api/cpn-plugins/v1/checkout.php?plugin=ntMalwareApi',
        'entitlement_endpoints' => [
            '/api/verify-plugin-grant.php',
            '/malware/v1/status',
        ],
    ],
    [
        'id' => 'premiumPlugin',
        'name' => isset($allowed['premiumPlugin']) ? $allowed['premiumPlugin'] : 'Premium Plugin Example',
        'pricing' => 'paid',
        'category' => 'Utility',
        'catalog_repo' => 'Control-Panel-Network/CPN-Plugins',
        'checkout' => '/api/cpn-plugins/v1/checkout.php?plugin=premiumPlugin',
    ],
    [
        'id' => 'paypalPremiumPlugin',
        'name' => isset($allowed['paypalPremiumPlugin']) ? $allowed['paypalPremiumPlugin'] : 'PayPal Premium Plugin Example',
        'pricing' => 'paid',
        'category' => 'Utility',
        'catalog_repo' => 'Control-Panel-Network/CPN-Plugins',
        'checkout' => '/api/cpn-plugins/v1/checkout.php?plugin=paypalPremiumPlugin',
    ],
];

$out = [
    'success' => true,
    'schema_version' => 1,
    'catalog' => 'cpn-plugins',
    'api_base' => 'https://api.newstargeted.com',
    'plugins' => $cpnPaid,
    'allowed_plugin_count' => count($allowed),
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
