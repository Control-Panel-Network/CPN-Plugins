<?php
if (!defined('NTHB_INIT')) {
    exit;
}
$isAdmin = nthb_is_admin();
$clientId = nthb_client_id();
$title = 'News Targeted Hosting Commerce';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo nthb_h($title); ?></title>
  <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="wrap">
  <header class="app">
    <div>
      <h1>News Targeted Hosting Commerce</h1>
      <p class="muted">CPN paid plugin <?php echo nthb_h(NTHB_VERSION); ?> · <?php echo nthb_h(NTHB_PLUGIN_ID); ?></p>
    </div>
    <nav>
      <?php if ($isAdmin): ?>
        <a href="?view=home">Dashboard</a>
        <a href="?view=clients">Clients</a>
        <a href="?view=products">Products</a>
        <a href="?view=orders">Orders</a>
        <a href="?view=invoices">Invoices</a>
        <a href="?view=subscriptions">Subscriptions</a>
        <a href="?view=ownership">Ownership</a>
        <a href="?view=license">License</a>
        <form class="inline" method="post"><input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>"><input type="hidden" name="action" value="admin_logout"><button class="btn" type="submit">Admin logout</button></form>
      <?php elseif ($clientId > 0): ?>
        <a href="?view=portal">My account</a>
        <form class="inline" method="post"><input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>"><input type="hidden" name="action" value="client_logout"><button class="btn" type="submit">Logout</button></form>
      <?php else: ?>
        <a href="?view=login">Admin</a>
        <a href="?view=client-login">Client login</a>
        <a href="?view=recovery">Recover password</a>
      <?php endif; ?>
    </nav>
  </header>

  <?php if ($notice !== ''): ?><div class="notice ok" role="status"><?php echo nthb_h($notice); ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="notice err" role="alert"><?php echo nthb_h($error); ?></div><?php endif; ?>

  <?php
  $licenseOk = !empty($license['ok']);
  if (!$licenseOk && $isAdmin): ?>
    <div class="notice err">License inactive: <?php echo nthb_h((string) ($license['message'] ?? '')); ?> <a href="?view=license">Configure license</a></div>
  <?php endif; ?>

  <?php
  $viewFile = dirname(__DIR__) . '/views/' . preg_replace('/[^a-z0-9\-]/', '', strtolower($view)) . '.php';
  if (!is_file($viewFile)) {
      $viewFile = dirname(__DIR__) . '/views/home.php';
  }
  require $viewFile;
  ?>

  <footer>
    News Targeted Hosting Commerce · Entitlement via api.newstargeted.com · Does not replace CPN panel admin auth
  </footer>
</div>
</body>
</html>
