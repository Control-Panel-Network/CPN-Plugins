<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_subscriptions_list(PDO $pdo)
{
    $sql = 'SELECT s.*, c.email AS client_email, p.name AS product_name, p.cpn_package
            FROM subscriptions s
            JOIN clients c ON c.id = s.client_id
            JOIN products p ON p.id = s.product_id
            ORDER BY s.id DESC';
    return $pdo->query($sql)->fetchAll();
}

function nthb_subscription_activate_from_order(PDO $pdo, $orderId)
{
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([(int) $orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        return [false, 'Order not found'];
    }
    $product = nthb_product_get($pdo, (int) $order['product_id']);
    if (!$product) {
        return [false, 'Product not found'];
    }
    $existing = $pdo->prepare('SELECT id FROM subscriptions WHERE order_id = ? LIMIT 1');
    $existing->execute([(int) $orderId]);
    if ($existing->fetch()) {
        $upd = $pdo->prepare(
            'UPDATE subscriptions SET status = ?, starts_at = COALESCE(starts_at, ?), cancelled_at = NULL, updated_at = ? WHERE order_id = ?'
        );
        $upd->execute(['active', nthb_now(), nthb_now(), (int) $orderId]);
        return [true, 'Subscription reactivated'];
    }

    $cycle = (string) ($product['billing_cycle'] ?? 'monthly');
    $ends = null;
    if ($cycle === 'monthly') {
        $ends = gmdate('Y-m-d H:i:s', strtotime('+1 month'));
    } elseif ($cycle === 'yearly') {
        $ends = gmdate('Y-m-d H:i:s', strtotime('+1 year'));
    }
    $now = nthb_now();
    $ins = $pdo->prepare(
        'INSERT INTO subscriptions (client_id, product_id, order_id, status, starts_at, ends_at, cancelled_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?)'
    );
    $ins->execute([
        (int) $order['client_id'],
        (int) $order['product_id'],
        (int) $orderId,
        'active',
        $now,
        $ends,
        $now,
        $now,
    ]);
    return [true, (int) $pdo->lastInsertId()];
}

function nthb_subscription_set_status(PDO $pdo, $id, $status)
{
    $status = nthb_status_allowed($status, ['pending', 'active', 'suspended', 'cancelled']);
    if ($status === null) {
        return [false, 'Invalid subscription status'];
    }
    $cancelled = $status === 'cancelled' ? nthb_now() : null;
    $stmt = $pdo->prepare(
        'UPDATE subscriptions SET status = ?, cancelled_at = COALESCE(?, cancelled_at), updated_at = ? WHERE id = ?'
    );
    $stmt->execute([$status, $cancelled, nthb_now(), (int) $id]);
    nthb_audit($pdo, 'admin', 'subscription.status', 'subscription', (int) $id, $status);
    return [true, 'Updated'];
}
