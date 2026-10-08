<?php
if (!defined('MRA_INIT')) {
    exit;
}
$user = mra_user();
$csrf = mra_csrf_token();
$title = 'Mr Agent';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo mra_h($title); ?> · CPN</title>
  <link rel="stylesheet" href="assets/app.css?v=<?php echo mra_h(MRA_VERSION); ?>">
</head>
<body>
  <header class="top">
    <div class="brand">
      <strong>Mr Agent</strong>
      <span class="muted">CPN AI assistant</span>
    </div>
    <nav>
      <?php if ($user !== ''): ?>
        <a href="?view=chat">Chat</a>
        <a href="?view=providers">Provider keys</a>
        <?php if (mra_is_owner()): ?>
          <a href="?view=settings">Owner settings</a>
        <?php endif; ?>
        <a href="?view=about">About</a>
        <form method="post" class="inline">
          <input type="hidden" name="csrf" value="<?php echo mra_h($csrf); ?>">
          <input type="hidden" name="action" value="logout">
          <button type="submit" class="linkish">Sign out (<?php echo mra_h($user); ?>)</button>
        </form>
      <?php endif; ?>
    </nav>
  </header>
  <main class="wrap">
    <?php if ($notice !== ''): ?>
      <p class="notice ok" role="status"><?php echo mra_h($notice); ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <p class="notice err" role="alert"><?php echo mra_h($error); ?></p>
    <?php endif; ?>
    <?php
    $viewFile = dirname(__FILE__) . '/' . $view . '.php';
    if (is_file($viewFile)) {
        require $viewFile;
    } else {
        echo '<p class="notice err">Unknown view.</p>';
    }
    ?>
  </main>
  <footer class="foot">
    <span>Mr Agent v<?php echo mra_h(MRA_VERSION); ?></span>
    <span class="muted">Provider API keys stay on this host under /var/lib/cpn/mr-agent/</span>
  </footer>
  <script>window.MRA = { csrf: <?php echo json_encode($csrf); ?>, version: <?php echo json_encode(MRA_VERSION); ?> };</script>
  <script src="assets/app.js?v=<?php echo mra_h(MRA_VERSION); ?>"></script>
</body>
</html>
