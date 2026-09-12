<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_products_list(PDO $pdo, $activeOnly = false)
{
    if ($activeOnly) {
        return $pdo->query('SELECT * FROM products WHERE active = 1 ORDER BY id DESC')->fetchAll();
    }
    return $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
}

function nthb_product_get(PDO $pdo, $id)
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function nthb_product_create(PDO $pdo, array $data)
{
    $sku = trim((string) ($data['sku'] ?? ''));
    $name = trim((string) ($data['name'] ?? ''));
    if ($sku === '' || $name === '') {
        return [false, 'SKU and name are required'];
    }
    $price = (int) round(((float) ($data['price'] ?? 0)) * 100);
    $currency = strtoupper(trim((string) ($data['currency'] ?? 'USD')));
    if ($currency === '') {
        $currency = 'USD';
    }
    $cycle = nthb_status_allowed((string) ($data['billing_cycle'] ?? 'monthly'), ['monthly', 'yearly', 'once']) ?: 'monthly';
    $now = nthb_now();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO products (sku, name, description, cpn_package, price_cents, currency, billing_cycle, active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)'
        );
        $stmt->execute([
            $sku,
            $name,
            trim((string) ($data['description'] ?? '')),
            trim((string) ($data['cpn_package'] ?? '')),
            $price,
            $currency,
            $cycle,
            $now,
            $now,
        ]);
        $id = (int) $pdo->lastInsertId();
        nthb_audit($pdo, 'admin', 'product.create', 'product', $id, $sku);
        return [true, $id];
    } catch (PDOException $e) {
        return [false, 'Could not create product (SKU may already exist)'];
    }
}

function nthb_product_set_active(PDO $pdo, $id, $active)
{
    $stmt = $pdo->prepare('UPDATE products SET active = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$active ? 1 : 0, nthb_now(), (int) $id]);
    return [true, 'Updated'];
}
