<?php
if (!defined('NTHB_INIT')) {
    exit;
}
nthb_require_client();
$cid = nthb_client_id();
$client = nthb_client_get($pdo, $cid);
$sites = nthb_ownership_for_client($pdo, $cid);
$subs = $pdo->prepare(
    'SELECT s.*, p.name AS product_name FROM subscriptions s JOIN products p ON p.id = s.product_id WHERE s.client_id = ? ORDER BY s.id DESC'
);
$subs->execute([$cid]);
$subRows = $subs->fetchAll();
$inv = $pdo->prepare('SELECT * FROM invoices WHERE client_id = ? ORDER BY id DESC');
$inv->execute([$cid]);
$invRows = $inv->fetchAll();
$cfgLocal = nthb_config_with_runtime_token(nthb_config());
?>
<div class="card">
  <h2>My account</h2>
  <p><?php echo nthb_h($client['display_name'] ?? ''); ?> · <?php echo nthb_h($client['email'] ?? ''); ?></p>
  <p class="muted">Status: <?php echo nthb_h($client['status'] ?? ''); ?> · CPN user: <?php echo nthb_h($client['cpn_username'] ?? ''); ?></p>
</div>
<div class="card">
  <h3>Sites</h3>
  <ul>
    <?php foreach ($sites as $s): ?>
      <li><?php echo nthb_h($s['domain']); ?> (<?php echo nthb_h($s['role']); ?>)</li>
    <?php endforeach; ?>
    <?php if (!$sites): ?><li class="muted">No sites assigned yet.</li><?php endif; ?>
  </ul>
</div>
<div class="card">
  <h3>Subscriptions</h3>
  <ul>
    <?php foreach ($subRows as $s): ?>
      <li><?php echo nthb_h($s['product_name']); ?> · <span class="badge <?php echo nthb_h($s['status']); ?>"><?php echo nthb_h($s['status']); ?></span></li>
    <?php endforeach; ?>
  </ul>
</div>
<div class="card">
  <h3>Invoices</h3>
  <table>
    <thead><tr><th>Number</th><th>Amount</th><th>Status</th><th>Pay</th></tr></thead>
    <tbody>
    <?php foreach ($invRows as $r): ?>
      <tr>
        <td><?php echo nthb_h($r['invoice_number']); ?></td>
        <td><?php echo nthb_h(nthb_money_cents($r['amount_cents'], $r['currency'])); ?></td>
        <td><?php echo nthb_h($r['status']); ?></td>
        <td>
          <?php if ($r['status'] !== 'paid'): $pay = nthb_paypal_link($cfgLocal, $r); if ($pay !== ''): ?>
            <a href="<?php echo nthb_h($pay); ?>" target="_blank" rel="noopener noreferrer">PayPal</a>
          <?php else: ?><span class="muted">Contact operator</span><?php endif; endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
