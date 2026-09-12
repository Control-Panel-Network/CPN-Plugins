<?php
if (!defined('NTHB_INIT')) {
    exit;
}
nthb_require_admin();
$rows = nthb_products_list($pdo);
?>
<div class="card">
  <h2>Products / packages</h2>
  <p class="muted">Map each product to a CPN package name for operator reference.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="product_create">
    <label>SKU</label><input name="sku" required>
    <label>Name</label><input name="name" required>
    <label>Description</label><textarea name="description" rows="2"></textarea>
    <label>CPN package</label><input name="cpn_package" placeholder="e.g. basic, pro">
    <label>Price</label><input name="price" type="number" step="0.01" min="0" value="9.99">
    <label>Currency</label><input name="currency" value="USD">
    <label>Billing cycle</label>
    <select name="billing_cycle"><option value="monthly">monthly</option><option value="yearly">yearly</option><option value="once">once</option></select>
    <p style="margin-top:10px;"><button class="btn btn-primary" type="submit">Add product</button></p>
  </form>
</div>
<div class="card">
  <table>
    <thead><tr><th>SKU</th><th>Name</th><th>CPN package</th><th>Price</th><th>Cycle</th><th>Active</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo nthb_h($r['sku']); ?></td>
        <td><?php echo nthb_h($r['name']); ?></td>
        <td><?php echo nthb_h($r['cpn_package']); ?></td>
        <td><?php echo nthb_h(nthb_money_cents($r['price_cents'], $r['currency'])); ?></td>
        <td><?php echo nthb_h($r['billing_cycle']); ?></td>
        <td><?php echo ((int) $r['active']) ? 'yes' : 'no'; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
