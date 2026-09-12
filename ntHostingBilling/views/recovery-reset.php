<?php
if (!defined('NTHB_INIT')) {
    exit;
}
$token = nthb_get('token', nthb_post('token'));
?>
<div class="card" style="max-width:420px;">
  <h2>Reset password</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="recovery_reset">
    <input type="hidden" name="token" value="<?php echo nthb_h($token); ?>">
    <label>New password</label><input name="password" type="password" required minlength="8" autocomplete="new-password">
    <p style="margin-top:10px;"><button class="btn btn-primary" type="submit">Update password</button></p>
  </form>
</div>
