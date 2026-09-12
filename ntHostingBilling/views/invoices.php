<?php
if (!defined('NTHB_INIT')) {
    exit;
}
nthb_require_admin();
$rows = nthb_invoices_list($pdo);
$cfgLocal = nthb_config_with_runtime_token(nthb_config());
?>
<div class="card">
  <h2>Invoices</h2>
  <p class="muted">Mark paid manually, or open PayPal link for the client.</p>
  <table>
    <thead><tr><th>Number</th><th>Client</th><th>Amount</th><th>Status</th><th>Due</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo nthb_h($r['invoice_number']); ?></td>
        <td><?php echo nthb_h($r['client_email']); ?></td>
        <td><?php echo nthb_h(nthb_money_cents($r['amount_cents'], $r['currency'])); ?></td>
        <td><span class="badge <?php echo nthb_h($r['status']); ?>"><?php echo nthb_h($r['status']); ?></span></td>
        <td><?php echo nthb_h((string) $r['due_at']); ?></td>
        <td>
          <?php if ($r['status'] !== 'paid'): ?>
            <form class="inline" method="post">
              <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
              <input type="hidden" name="action" value="invoice_mark_paid">
              <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
              <input type="hidden" name="payment_method" value="manual">
              <button class="btn btn-ok" type="submit">Mark paid</button>
            </form>
            <?php $pay = nthb_paypal_link($cfgLocal, $r); if ($pay !== ''): ?>
              <a class="btn" href="<?php echo nthb_h($pay); ?>" target="_blank" rel="noopener noreferrer">PayPal</a>
            <?php endif; ?>
          <?php else: ?>
            <span class="muted"><?php echo nthb_h((string) $r['paid_at']); ?> · <?php echo nthb_h($r['payment_method']); ?></span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
