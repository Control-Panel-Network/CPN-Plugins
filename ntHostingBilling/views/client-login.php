<?php
if (!defined('NTHB_INIT')) {
    exit;
}
?>
<div class="card" style="max-width:420px;">
  <h2>Client login</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="client_login">
    <label>Email</label><input name="email" type="email" required>
    <label>Password</label><input name="password" type="password" required autocomplete="current-password">
    <p style="margin-top:10px;"><button class="btn btn-primary" type="submit">Sign in</button></p>
  </form>
  <p class="muted"><a href="?view=recovery">Forgot password</a></p>
</div>
