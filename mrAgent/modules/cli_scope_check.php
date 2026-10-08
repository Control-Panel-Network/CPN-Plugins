#!/usr/bin/env php
<?php
/**
 * Functional check: two fake users must not see each other's websites/mailboxes/packages.
 *
 * Usage:
 *   php modules/cli_scope_check.php
 *
 * Exit 0 = pass; 1 = fail.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

define('MRA_INIT', true);

$tmp = sys_get_temp_dir() . '/mra-scope-' . getmypid() . '-' . bin2hex(random_bytes(4));
@mkdir($tmp . '/sites', 0700, true);
putenv('CPN_DATA_DIR=' . $tmp);

require_once __DIR__ . '/bootstrap.php';

function mra_scope_check_fail($msg)
{
    fwrite(STDERR, "FAIL: $msg\n");
    exit(1);
}

function mra_scope_check_impersonate($user, $role, $packageId)
{
    mra_session_start();
    $_SESSION = [];
    $_SESSION['mra_user'] = $user;
    $_SESSION['mra_role'] = $role;
    $_SESSION['mra_package'] = $packageId;
    $_SESSION['mra_at'] = time();
}

// Fake registry: user1 owns many sites; user2 owns one.
$sites = [
    ['domain' => 'user1-a.example', 'owner' => 'user1', 'docroot' => '/home/user1-a.example/public_html'],
    ['domain' => 'user1-b.example', 'owner' => 'user1', 'docroot' => '/home/user1-b.example/public_html'],
    ['domain' => 'user1-c.example', 'owner' => 'user1', 'docroot' => '/home/user1-c.example/public_html'],
    ['domain' => 'user2-only.example', 'owner' => 'user2', 'docroot' => '/home/user2-only.example/public_html'],
];
foreach ($sites as $s) {
    file_put_contents(
        $tmp . '/sites/' . $s['domain'] . '.json',
        json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
}

file_put_contents(
    $tmp . '/packages.json',
    json_encode([
        'packages' => [
            ['id' => 'pkg_user1', 'name' => 'User1 Plan', 'domains' => 10, 'emails' => 10],
            ['id' => 'pkg_user2', 'name' => 'User2 Plan', 'domains' => 1, 'emails' => 1],
            ['id' => 'Default', 'name' => 'Default', 'domains' => -1, 'emails' => -1],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

file_put_contents(
    $tmp . '/mail-accounts.json',
    json_encode([
        'schema_version' => 1,
        'accounts' => [
            ['id' => '1', 'address' => 'a@user1-a.example', 'domain' => 'user1-a.example', 'enabled' => true, 'smtp_mode' => 'postfix_local', 'smtp_password' => 'SECRET1', 'mailbox_password' => 'SECRET1b'],
            ['id' => '2', 'address' => 'b@user1-a.example', 'domain' => 'user1-a.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '3', 'address' => 'c@user1-b.example', 'domain' => 'user1-b.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '4', 'address' => 'd@user1-b.example', 'domain' => 'user1-b.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '5', 'address' => 'e@user1-c.example', 'domain' => 'user1-c.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '6', 'address' => 'f@user1-c.example', 'domain' => 'user1-c.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '7', 'address' => 'g@user1-a.example', 'domain' => 'user1-a.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '8', 'address' => 'h@user1-a.example', 'domain' => 'user1-a.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '9', 'address' => 'i@user1-b.example', 'domain' => 'user1-b.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '10', 'address' => 'j@user1-c.example', 'domain' => 'user1-c.example', 'enabled' => true, 'smtp_mode' => 'postfix_local'],
            ['id' => '11', 'address' => 'solo@user2-only.example', 'domain' => 'user2-only.example', 'enabled' => true, 'smtp_mode' => 'postfix_local', 'smtp_password' => 'SECRET2'],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);
@chmod($tmp . '/mail-accounts.json', 0600);

$cfg = ['plugin_enabled' => true, 'visibility' => 'all_authenticated', 'domain' => 'lab.example'];

// --- user2 ---
mra_scope_check_impersonate('user2', 'user', 'pkg_user2');
$w2 = mra_tool_execute('list_websites', [], $cfg, 'user2');
$m2 = mra_tool_execute('list_mailboxes', [], $cfg, 'user2');
$p2 = mra_tool_execute('list_packages', [], $cfg, 'user2');
$dns2 = mra_tool_execute('dns_skill_status', [], $cfg, 'user2');

if (empty($w2['ok'])) {
    mra_scope_check_fail('user2 list_websites failed');
}
$domains2 = array_map(function ($r) {
    return $r['domain'];
}, $w2['websites'] ?? []);
if ($domains2 !== ['user2-only.example']) {
    mra_scope_check_fail('user2 websites leaked: ' . json_encode($domains2));
}
if ((int) ($m2['count'] ?? 0) !== 1) {
    mra_scope_check_fail('user2 should see 1 mailbox, got ' . (int) ($m2['count'] ?? 0));
}
$addr2 = (string) (($m2['mailboxes'][0]['address'] ?? ''));
if ($addr2 !== 'solo@user2-only.example') {
    mra_scope_check_fail('user2 mailbox wrong: ' . $addr2);
}
foreach (($m2['mailboxes'] ?? []) as $mb) {
    $blob = json_encode($mb);
    if (stripos((string) $blob, 'SECRET') !== false || stripos((string) $blob, 'password') !== false) {
        mra_scope_check_fail('mailbox response leaked secrets');
    }
}
if ((int) ($p2['count'] ?? 0) !== 1 || strtolower((string) ($p2['packages'][0]['id'] ?? '')) !== 'pkg_user2') {
    mra_scope_check_fail('user2 packages not scoped');
}
if (!empty($dns2['ok'])) {
    mra_scope_check_fail('user2 should be denied host DNS stub');
}

// Path deny
if (mra_scope_path_allowed('/home/user1-a.example/public_html')) {
    mra_scope_check_fail('user2 must not access user1 home path');
}
if (!mra_scope_path_allowed('/home/user2-only.example/public_html')) {
    mra_scope_check_fail('user2 should access own home path');
}

// --- user1 ---
mra_scope_check_impersonate('user1', 'user', 'pkg_user1');
$w1 = mra_tool_execute('list_websites', [], $cfg, 'user1');
$m1 = mra_tool_execute('list_mailboxes', [], $cfg, 'user1');
if ((int) ($w1['count'] ?? 0) !== 3) {
    mra_scope_check_fail('user1 should see 3 websites');
}
if ((int) ($m1['count'] ?? 0) !== 10) {
    mra_scope_check_fail('user1 should see 10 mailboxes, got ' . (int) ($m1['count'] ?? 0));
}
foreach (($w1['websites'] ?? []) as $row) {
    if (($row['domain'] ?? '') === 'user2-only.example') {
        mra_scope_check_fail('user1 saw user2 site');
    }
}

// --- admin ---
mra_scope_check_impersonate('cpnowner', 'owner', '');
$wa = mra_tool_execute('list_websites', [], $cfg, 'cpnowner');
$ma = mra_tool_execute('list_mailboxes', [], $cfg, 'cpnowner');
$pa = mra_tool_execute('list_packages', [], $cfg, 'cpnowner');
if ((int) ($wa['count'] ?? 0) !== 4) {
    mra_scope_check_fail('admin should see all 4 websites');
}
if ((int) ($ma['count'] ?? 0) !== 11) {
    mra_scope_check_fail('admin should see all mailboxes');
}
if ((int) ($pa['count'] ?? 0) !== 3) {
    mra_scope_check_fail('admin should see all packages');
}

// Cleanup
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($it as $f) {
    $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($tmp);

echo json_encode([
    'ok' => true,
    'checks' => 'user1/user2/admin isolation passed',
    'version' => MRA_VERSION,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
exit(0);
