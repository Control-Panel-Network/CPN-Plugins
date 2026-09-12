<?php
if (!defined('NTHB_INIT')) {
    exit;
}

/**
 * Build PayPal pay URL for an unpaid invoice.
 *
 * @param array<string,mixed> $cfg
 * @param array<string,mixed> $invoice
 */
function nthb_paypal_link(array $cfg, array $invoice)
{
    $custom = trim((string) ($cfg['paypal_payment_link'] ?? ''));
    if ($custom !== '') {
        $sep = strpos($custom, '?') === false ? '?' : '&';
        return $custom . $sep . 'invoice=' . urlencode((string) $invoice['invoice_number']);
    }
    $me = trim((string) ($cfg['paypal_me_url'] ?? ''));
    if ($me === '') {
        return '';
    }
    $amount = number_format(((int) $invoice['amount_cents']) / 100, 2, '.', '');
    return rtrim($me, '/') . '/' . $amount;
}

/**
 * Dashboard counters.
 *
 * @return array<string,int>
 */
function nthb_stats(PDO $pdo)
{
    $out = [
        'clients' => 0,
        'products' => 0,
        'orders' => 0,
        'invoices_unpaid' => 0,
        'subscriptions_active' => 0,
        'sites' => 0,
    ];
    $map = [
        'clients' => 'SELECT COUNT(*) AS c FROM clients',
        'products' => 'SELECT COUNT(*) AS c FROM products',
        'orders' => 'SELECT COUNT(*) AS c FROM orders',
        'invoices_unpaid' => "SELECT COUNT(*) AS c FROM invoices WHERE status = 'unpaid'",
        'subscriptions_active' => "SELECT COUNT(*) AS c FROM subscriptions WHERE status = 'active'",
        'sites' => 'SELECT COUNT(*) AS c FROM site_ownership',
    ];
    foreach ($map as $key => $sql) {
        $out[$key] = (int) ($pdo->query($sql)->fetch()['c'] ?? 0);
    }
    return $out;
}
