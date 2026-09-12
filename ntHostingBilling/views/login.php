<?php
if (!defined('NTHB_INIT')) {
    exit;
}
?>
<div class="card" style="max-width:420px;">
  <h2>Admin login</h2>
  <p class="muted">Operator password from config.php (not CPN panel account).</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="admin_login">
    <label for="password">Admin password</label>
    <input id="password" type="password" name="password" required autocomplete="current-password">
    <p style="margin-top:12px;"><button class="btn btn-primary" type="submit">Sign in</button></p>
  </form>
</div>
