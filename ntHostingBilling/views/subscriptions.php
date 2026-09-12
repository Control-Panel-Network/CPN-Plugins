<?php
if (!defined('NTHB_INIT')) {
    exit;
}
nthb_require_admin();
$rows = nthb_subscriptions_list($pdo);
?>
<div class="card">
  <h2>Subscriptions</h2>
  <table>
    <thead><tr><th>ID</th><th>Client</th><th>Product</th><th>CPN package</th><th>Status</th><th>Ends</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo (int) $r['id']; ?></td>
        <td><?php echo nthb_h($r['client_email']); ?></td>
        <td><?php echo nthb_h($r['product_name']); ?></td>
        <td><?php echo nthb_h($r['cpn_package']); ?></td>
        <td><span class="badge <?php echo nthb_h($r['status']); ?>"><?php echo nthb_h($r['status']); ?></span></td>
        <td><?php echo nthb_h((string) $r['ends_at']); ?></td>
        <td>
          <?php foreach (['active', 'suspended', 'cancelled'] as $st): ?>
            <form class="inline" method="post">
              <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
              <input type="hidden" name="action" value="subscription_status">
              <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
              <input type="hidden" name="status" value="<?php echo $st; ?>">
              <button class="btn" type="submit"><?php echo $st; ?></button>
            </form>
          <?php endforeach; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
