<?php
if (!defined('NTHB_INIT')) {
    exit;
}
nthb_require_admin();
$rows = nthb_ownership_list($pdo);
$clients = nthb_clients_list($pdo);
?>
<div class="card">
  <h2>Site ownership</h2>
  <p class="muted">Bind client accounts to CPN site domains with manage/billing roles.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="ownership_assign">
    <label>Client</label>
    <select name="client_id" required>
      <?php foreach ($clients as $c): ?>
        <option value="<?php echo (int) $c['id']; ?>"><?php echo nthb_h($c['email']); ?></option>
      <?php endforeach; ?>
    </select>
    <label>Domain</label><input name="domain" placeholder="test2.newstargeted.com" required>
    <label>Role</label>
    <select name="role"><option value="owner">owner</option><option value="admin">admin</option><option value="billing">billing</option><option value="viewer">viewer</option></select>
    <p style="margin-top:10px;"><button class="btn btn-primary" type="submit">Assign</button></p>
  </form>
</div>
<div class="card">
  <table>
    <thead><tr><th>Domain</th><th>Client</th><th>CPN user</th><th>Role</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo nthb_h($r['domain']); ?></td>
        <td><?php echo nthb_h($r['client_email']); ?></td>
        <td><?php echo nthb_h($r['cpn_username']); ?></td>
        <td><?php echo nthb_h($r['role']); ?></td>
        <td>
          <form class="inline" method="post">
            <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
            <input type="hidden" name="action" value="ownership_remove">
            <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
            <button class="btn" type="submit">Remove</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
