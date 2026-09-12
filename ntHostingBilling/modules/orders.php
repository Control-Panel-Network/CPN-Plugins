<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_orders_list(PDO $pdo)
{
    $sql = 'SELECT o.*, c.email AS client_email, p.name AS product_name
            FROM orders o
            JOIN clients c ON c.id = o.client_id
            JOIN products p ON p.id = o.product_id
            ORDER BY o.id DESC';
    return $pdo->query($sql)->fetchAll();
}

function nthb_order_create(PDO $pdo, $clientId, $productId, $notes = '')
{
    $client = nthb_client_get($pdo, $clientId);
    $product = nthb_product_get($pdo, $productId);
    if (!$client || !$product) {
        return [false, 'Client or product not found'];
    }
    if (!(int) ($product['active'] ?? 0)) {
        return [false, 'Product is inactive'];
    }
    $now = nthb_now();
    $stmt = $pdo->prepare(
        'INSERT INTO orders (client_id, product_id, status, amount_cents, currency, notes, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int) $clientId,
        (int) $productId,
        'pending',
        (int) $product['price_cents'],
        (string) $product['currency'],
        trim((string) $notes),
        $now,
        $now,
    ]);
    $orderId = (int) $pdo->lastInsertId();
    $inv = nthb_invoice_create_for_order($pdo, $orderId);
    if (!$inv[0]) {
        return [false, 'Order created but invoice failed: ' . $inv[1]];
    }
    nthb_audit($pdo, 'admin', 'order.create', 'order', $orderId, 'invoice=' . $inv[1]);
    return [true, $orderId];
}

function nthb_order_set_status(PDO $pdo, $id, $status)
{
    $status = nthb_status_allowed($status, ['pending', 'paid', 'cancelled', 'refunded']);
    if ($status === null) {
        return [false, 'Invalid order status'];
    }
    $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$status, nthb_now(), (int) $id]);
    return [true, 'Updated'];
}
