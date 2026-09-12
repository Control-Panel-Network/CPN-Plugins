<?php
if (!defined('NTHB_INIT')) {
    exit;
}
nthb_require_admin();
$cfg = nthb_config_with_runtime_token(nthb_config());
?>
<div class="card">
  <h2>License and billing settings</h2>
  <p class="muted">Paid entitlement uses api.newstargeted.com (plugin grant, activation key, or entitlement token). Secrets stay in config.php.</p>
  <p>Status: <strong><?php echo !empty($license['ok']) ? 'OK' : 'Locked'; ?></strong> · <?php echo nthb_h((string) ($license['message'] ?? '')); ?></p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo nthb_h(nthb_csrf_token()); ?>">
    <input type="hidden" name="action" value="save_license">
    <label for="api_base">API base</label>
    <input id="api_base" name="api_base" value="<?php echo nthb_h((string) ($cfg['api_base'] ?? '')); ?>">
    <label for="license_email">License email</label>
    <input id="license_email" name="license_email" type="email" value="<?php echo nthb_h((string) ($cfg['license_email'] ?? '')); ?>">
    <label for="activation_key">Activation key</label>
    <input id="activation_key" name="activation_key" value="<?php echo nthb_h((string) ($cfg['activation_key'] ?? '')); ?>">
    <label for="entitlement_token">Entitlement token</label>
    <input id="entitlement_token" name="entitlement_token" value="<?php echo nthb_h((string) ($cfg['entitlement_token'] ?? '')); ?>">
    <label for="paypal_me_url">PayPal.me URL</label>
    <input id="paypal_me_url" name="paypal_me_url" value="<?php echo nthb_h((string) ($cfg['paypal_me_url'] ?? '')); ?>">
    <label for="paypal_payment_link">PayPal payment link</label>
    <input id="paypal_payment_link" name="paypal_payment_link" value="<?php echo nthb_h((string) ($cfg['paypal_payment_link'] ?? '')); ?>">
    <label for="admin_password">Set admin password (leave blank to keep)</label>
    <input id="admin_password" name="admin_password" type="password" autocomplete="new-password">
    <p style="margin-top:12px;"><button class="btn btn-primary" type="submit">Save</button></p>
  </form>
</div>
