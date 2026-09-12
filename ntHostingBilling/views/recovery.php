<?php
if (!defined('NTHB_INIT')) {
    exit;
}
?>
<div class="card" style="max-width:420px;">
  <h2>Recover password</h2>
  <p class="muted">Client account recovery only. CPN panel admin passwords are unchanged.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="recovery_request">
    <label>Email</label><input name="email" type="email" required>
    <p style="margin-top:10px;"><button class="btn btn-primary" type="submit">Send recovery link</button></p>
  </form>
</div>
