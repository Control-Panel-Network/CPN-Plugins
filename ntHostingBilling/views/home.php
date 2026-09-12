<?php
if (!defined('NTHB_INIT')) {
    exit;
}
if (!nthb_is_admin()) {
    echo '<div class="card"><p>Sign in as admin to manage hosting commerce.</p><p><a class="btn btn-primary" href="?view=login">Admin login</a></p></div>';
    return;
}
$stats = nthb_stats($pdo);
?>
<div class="card">
  <h2>Dashboard</h2>
  <p class="muted">Hosting business layer for this CPN site. License: <?php echo !empty($license['ok']) ? 'active (' . nthb_h((string) $license['via']) . ')' : 'inactive'; ?>.</p>
  <div class="grid">
    <?php foreach ($stats as $label => $count): ?>
      <div class="stat card"><span class="muted"><?php echo nthb_h(str_replace('_', ' ', $label)); ?></span><strong><?php echo (int) $count; ?></strong></div>
    <?php endforeach; ?>
  </div>
</div>
