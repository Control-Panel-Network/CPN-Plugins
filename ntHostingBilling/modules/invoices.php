<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_invoices_list(PDO $pdo)
{
    $sql = 'SELECT i.*, c.email AS client_email
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            ORDER BY i.id DESC';
    return $pdo->query($sql)->fetchAll();
}

function nthb_invoice_get(PDO $pdo, $id)
{
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function nthb_next_invoice_number(PDO $pdo)
{
    $year = gmdate('Y');
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM invoices WHERE invoice_number LIKE 'INV-{$year}-%'");
    $count = (int) ($stmt->fetch()['c'] ?? 0);
    return sprintf('INV-%s-%05d', $year, $count + 1);
}

function nthb_invoice_create_for_order(PDO $pdo, $orderId)
{
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([(int) $orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        return [false, 'Order not found'];
    }
    $now = nthb_now();
    $due = gmdate('Y-m-d H:i:s', time() + 7 * 86400);
    $number = nthb_next_invoice_number($pdo);
    $ins = $pdo->prepare(
        'INSERT INTO invoices (order_id, client_id, invoice_number, status, amount_cents, currency, due_at, paid_at, payment_method, payment_ref, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?)'
    );
    $ins->execute([
        (int) $order['id'],
        (int) $order['client_id'],
        $number,
        'unpaid',
        (int) $order['amount_cents'],
        (string) $order['currency'],
        $due,
        'manual',
        '',
        $now,
        $now,
    ]);
    return [true, $number];
}

/**
 * Mark invoice paid and activate subscription.
 */
function nthb_invoice_mark_paid(PDO $pdo, $invoiceId, $method = 'manual', $ref = '')
{
    $invoice = nthb_invoice_get($pdo, $invoiceId);
    if (!$invoice) {
        return [false, 'Invoice not found'];
    }
    if (($invoice['status'] ?? '') === 'paid') {
        return [true, 'Already paid'];
    }
    $now = nthb_now();
    $method = trim((string) $method);
    if ($method === '') {
        $method = 'manual';
    }
    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare(
            'UPDATE invoices SET status = ?, paid_at = ?, payment_method = ?, payment_ref = ?, updated_at = ? WHERE id = ?'
        );
        $upd->execute(['paid', $now, $method, trim((string) $ref), $now, (int) $invoiceId]);
        nthb_order_set_status($pdo, (int) $invoice['order_id'], 'paid');
        $sub = nthb_subscription_activate_from_order($pdo, (int) $invoice['order_id']);
        if (!$sub[0]) {
            throw new RuntimeException($sub[1]);
        }
        $pdo->commit();
        nthb_audit($pdo, 'admin', 'invoice.paid', 'invoice', (int) $invoiceId, $method);
        return [true, 'Marked paid; subscription active'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [false, $e->getMessage()];
    }
}
