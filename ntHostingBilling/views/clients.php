<?php
if (!defined('NTHB_INIT')) {
    exit;
}
nthb_require_admin();
$rows = nthb_clients_list($pdo);
?>
<div class="card">
  <h2>Clients</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="client_create">
    <label>Email</label><input name="email" type="email" required>
    <label>Display name</label><input name="display_name">
    <label>Linked CPN username</label><input name="cpn_username" placeholder="optional">
    <label>Initial password</label><input name="password" type="password" autocomplete="new-password">
    <p style="margin-top:10px;"><button class="btn btn-primary" type="submit">Invite / create client</button></p>
  </form>
</div>
<div class="card">
  <table>
    <thead><tr><th>ID</th><th>Email</th><th>CPN user</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo (int) $r['id']; ?></td>
        <td><?php echo nthb_h($r['email']); ?><br><span class="muted"><?php echo nthb_h($r['display_name']); ?></span></td>
        <td><?php echo nthb_h($r['cpn_username']); ?></td>
        <td><span class="badge <?php echo nthb_h($r['status']); ?>"><?php echo nthb_h($r['status']); ?></span></td>
        <td>
          <?php foreach (['active', 'suspended', 'cancelled'] as $st): ?>
            <form class="inline" method="post">
              <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
              <input type="hidden" name="action" value="client_status">
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
