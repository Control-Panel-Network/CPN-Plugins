<?php
if (!defined('MRA_INIT')) {
    exit;
}
?>
<section class="card narrow">
  <h1>Sign in to Mr Agent</h1>
  <p class="muted">Use any username plus the site access password set by the CPN owner. Usernames <code>owner</code>, <code>admin</code>, or <code>cpnowner</code> get owner settings access.</p>
  <form method="post" class="stack">
    <input type="hidden" name="csrf" value="<?php echo mra_h(mra_csrf_token()); ?>">
    <input type="hidden" name="action" value="login">
    <label for="username">Username</label>
    <input id="username" name="username" autocomplete="username" required maxlength="64">
    <label for="password">Access password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <label for="package_id">Package id (optional, for packages visibility)</label>
    <input id="package_id" name="package_id" maxlength="64" placeholder="e.g. Default">
    <button type="submit" class="btn">Sign in</button>
  </form>
</section>
