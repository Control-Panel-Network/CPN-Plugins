<?php
if (!defined('NTHB_INIT')) {
    exit;
}
nthb_require_admin();
$orders = nthb_orders_list($pdo);
$clients = nthb_clients_list($pdo);
$products = nthb_products_list($pdo, true);
?>
<div class="card">
  <h2>Orders</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="order_create">
    <label>Client</label>
    <select name="client_id" required>
      <?php foreach ($clients as $c): ?>
        <option value="<?php echo (int) $c['id']; ?>"><?php echo nthb_h($c['email']); ?></option>
      <?php endforeach; ?>
    </select>
    <label>Product</label>
    <select name="product_id" required>
      <?php foreach ($products as $p): ?>
        <option value="<?php echo (int) $p['id']; ?>"><?php echo nthb_h($p['name'] . ' (' . nthb_money_cents($p['price_cents'], $p['currency']) . ')'); ?></option>
      <?php endforeach; ?>
    </select>
    <label>Notes</label><input name="notes">
    <p style="margin-top:10px;"><button class="btn btn-primary" type="submit">Create order + invoice</button></p>
  </form>
</div>
<div class="card">
  <table>
    <thead><tr><th>ID</th><th>Client</th><th>Product</th><th>Amount</th><th>Status</th><th>Created</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $r): ?>
      <tr>
        <td><?php echo (int) $r['id']; ?></td>
        <td><?php echo nthb_h($r['client_email']); ?></td>
        <td><?php echo nthb_h($r['product_name']); ?></td>
        <td><?php echo nthb_h(nthb_money_cents($r['amount_cents'], $r['currency'])); ?></td>
        <td><span class="badge <?php echo nthb_h($r['status']); ?>"><?php echo nthb_h($r['status']); ?></span></td>
        <td><?php echo nthb_h($r['created_at']); ?> UTC</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
