<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Per-user data isolation for Host (and Site) Mr Agent installs.
 *
 * Identity comes from the panel float-chat bridge (trusted CLI stdin) or the
 * Mr Agent session after login. Skills must filter through these helpers so
 * user2 never sees user1 websites, mailboxes, packages, or /home paths.
 */

/**
 * CPN data directory (panel host state). Never write secrets here from skills.
 */
function mra_cpn_data_dir()
{
    $env = getenv('CPN_DATA_DIR');
    if (is_string($env) && $env !== '' && is_dir($env)) {
        return rtrim($env, "/\\");
    }
    return '/var/lib/cpn';
}

/**
 * @return array{username:string,role:string,package_id:string,is_admin:bool}
 */
function mra_scope_actor()
{
    $username = strtolower(trim((string) mra_user()));
    $role = strtolower(trim((string) mra_role()));
    mra_session_start();
    $packageId = isset($_SESSION['mra_package']) ? strtolower(trim((string) $_SESSION['mra_package'])) : '';
    $isAdmin = ($role === 'owner')
        || in_array($username, ['owner', 'admin', 'cpnowner'], true);
    return [
        'username' => $username,
        'role' => $role !== '' ? $role : 'user',
        'package_id' => $packageId,
        'is_admin' => $isAdmin,
    ];
}

/**
 * Panel admin / owner role may see host-wide inventory (same as panel pages).
 */
function mra_scope_is_admin($username = null)
{
    if ($username === null) {
        return !empty(mra_scope_actor()['is_admin']);
    }
    $username = strtolower(trim((string) $username));
    $role = strtolower(trim((string) mra_role()));
    return ($role === 'owner') || in_array($username, ['owner', 'admin', 'cpnowner'], true);
}

/**
 * @return array<int,array<string,mixed>>
 */
function mra_scope_load_site_acl_grants()
{
    $path = mra_cpn_data_dir() . '/site-acl.json';
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }
    $raw = (string) @file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['grants']) || !is_array($data['grants'])) {
        return [];
    }
    return $data['grants'];
}

/**
 * Whether actor may manage a site (owner match or site-acl grant). Admins: always.
 *
 * @param array<string,mixed> $siteRow domain/owner keys from registry JSON
 */
function mra_scope_can_manage_site(array $actor, array $siteRow)
{
    if (!empty($actor['is_admin'])) {
        return true;
    }
    $username = (string) ($actor['username'] ?? '');
    if ($username === '') {
        return false;
    }
    $owner = strtolower(trim((string) ($siteRow['owner'] ?? '')));
    $domain = strtolower(trim((string) ($siteRow['domain'] ?? '')));
    if ($owner !== '' && strcasecmp($owner, $username) === 0) {
        return true;
    }
    foreach (mra_scope_load_site_acl_grants() as $grant) {
        if (!is_array($grant)) {
            continue;
        }
        $member = strtolower(trim((string) ($grant['member'] ?? '')));
        if ($member === '' || strcasecmp($member, $username) !== 0) {
            continue;
        }
        $grantDomain = strtolower(trim((string) ($grant['domain'] ?? '')));
        if ($grantDomain !== '' && $domain !== '' && strcasecmp($grantDomain, $domain) === 0) {
            return true;
        }
        $allOwnedBy = strtolower(trim((string) ($grant['all_owned_by'] ?? '')));
        if ($allOwnedBy !== '' && $owner !== '' && strcasecmp($allOwnedBy, $owner) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Domains the actor may see/manage (deny by default).
 *
 * @return array<string,true> lowercase domain => true
 */
function mra_scope_allowed_domains(array $actor = null)
{
    if ($actor === null) {
        $actor = mra_scope_actor();
    }
    $map = [];
    foreach (mra_scope_list_sites_raw() as $row) {
        if (!mra_scope_can_manage_site($actor, $row)) {
            continue;
        }
        $d = strtolower(trim((string) ($row['domain'] ?? '')));
        if ($d !== '') {
            $map[$d] = true;
        }
    }
    return $map;
}

/**
 * @return array<int,array<string,mixed>>
 */
function mra_scope_list_sites_raw()
{
    $dir = mra_cpn_data_dir() . '/sites';
    if (!is_dir($dir)) {
        return [];
    }
    $files = glob($dir . '/*.json');
    if (!is_array($files)) {
        return [];
    }
    sort($files);
    $out = [];
    foreach ($files as $file) {
        if (!is_file($file) || !is_readable($file)) {
            continue;
        }
        $raw = (string) @file_get_contents($file);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            continue;
        }
        $domain = (string) ($data['domain'] ?? $data['name'] ?? pathinfo($file, PATHINFO_FILENAME));
        $domain = strtolower(trim($domain));
        if ($domain === '' || $domain === '.' || $domain === '..' || $domain === 'filegator-lab.local') {
            continue;
        }
        $out[] = [
            'domain' => $domain,
            'php' => isset($data['php_version']) ? (string) $data['php_version'] : (isset($data['php']) ? (string) $data['php'] : ''),
            'owner' => isset($data['owner']) ? (string) $data['owner'] : (isset($data['username']) ? (string) $data['username'] : ''),
            'docroot' => isset($data['docroot']) ? (string) $data['docroot'] : (isset($data['document_root']) ? (string) $data['document_root'] : ''),
            '_file' => $file,
        ];
    }
    return $out;
}

/**
 * Sites visible to the current actor (scoped).
 *
 * @return array<int,array<string,mixed>>
 */
function mra_scope_list_websites(array $actor = null)
{
    if ($actor === null) {
        $actor = mra_scope_actor();
    }
    if ((string) ($actor['username'] ?? '') === '') {
        return [];
    }
    $out = [];
    foreach (mra_scope_list_sites_raw() as $row) {
        if (!mra_scope_can_manage_site($actor, $row)) {
            continue;
        }
        unset($row['_file']);
        $out[] = $row;
    }
    return $out;
}

/**
 * Packages visible to the actor: admin sees all; others only assigned package_id.
 *
 * @param array<int,array<string,mixed>> $packages
 * @return array<int,array<string,mixed>>
 */
function mra_scope_filter_packages(array $packages, array $actor = null)
{
    if ($actor === null) {
        $actor = mra_scope_actor();
    }
    if ((string) ($actor['username'] ?? '') === '') {
        return [];
    }
    if (!empty($actor['is_admin'])) {
        return $packages;
    }
    $pkgId = strtolower(trim((string) ($actor['package_id'] ?? '')));
    if ($pkgId === '') {
        // No assigned package: show only packages named after the user (username_*) or exact name match.
        $user = strtolower((string) $actor['username']);
        $out = [];
        foreach ($packages as $pkg) {
            if (!is_array($pkg)) {
                continue;
            }
            $id = strtolower(trim((string) ($pkg['id'] ?? '')));
            $name = strtolower(trim((string) ($pkg['name'] ?? '')));
            if ($id === $user || $name === $user || strpos($id, $user . '_') === 0) {
                $out[] = $pkg;
            }
        }
        return $out;
    }
    $out = [];
    foreach ($packages as $pkg) {
        if (!is_array($pkg)) {
            continue;
        }
        $id = strtolower(trim((string) ($pkg['id'] ?? '')));
        $name = strtolower(trim((string) ($pkg['name'] ?? '')));
        if ($id === $pkgId || $name === $pkgId) {
            $out[] = $pkg;
        }
    }
    return $out;
}

/**
 * Mail accounts from /var/lib/cpn/mail-accounts.json (no secrets in return).
 *
 * @return array<int,array<string,mixed>>
 */
function mra_scope_list_mailboxes_raw()
{
    $path = mra_cpn_data_dir() . '/mail-accounts.json';
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }
    $raw = (string) @file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    $list = isset($data['accounts']) && is_array($data['accounts']) ? $data['accounts'] : [];
    $out = [];
    foreach ($list as $acc) {
        if (!is_array($acc)) {
            continue;
        }
        $address = strtolower(trim((string) ($acc['address'] ?? '')));
        if ($address === '' || strpos($address, '@') === false) {
            continue;
        }
        $domain = strtolower(trim((string) ($acc['domain'] ?? '')));
        if ($domain === '') {
            $parts = explode('@', $address, 2);
            $domain = isset($parts[1]) ? $parts[1] : '';
        }
        $out[] = [
            'id' => (string) ($acc['id'] ?? ''),
            'address' => $address,
            'domain' => $domain,
            'enabled' => !empty($acc['enabled']),
            'smtp_mode' => (string) ($acc['smtp_mode'] ?? ''),
        ];
    }
    return $out;
}

/**
 * Mailboxes whose domain is in the actor's allowed site set (deny by default).
 *
 * @return array<int,array<string,mixed>>
 */
function mra_scope_list_mailboxes(array $actor = null)
{
    if ($actor === null) {
        $actor = mra_scope_actor();
    }
    if ((string) ($actor['username'] ?? '') === '') {
        return [];
    }
    if (!empty($actor['is_admin'])) {
        return mra_scope_list_mailboxes_raw();
    }
    $allowed = mra_scope_allowed_domains($actor);
    if (empty($allowed)) {
        return [];
    }
    $out = [];
    foreach (mra_scope_list_mailboxes_raw() as $mb) {
        $d = strtolower(trim((string) ($mb['domain'] ?? '')));
        if ($d !== '' && isset($allowed[$d])) {
            $out[] = $mb;
            continue;
        }
        // Nested subdomain: allow if any allowed domain is a suffix parent.
        $ok = false;
        foreach ($allowed as $siteDomain => $_) {
            if ($d === $siteDomain || substr($d, -strlen('.' . $siteDomain)) === ('.' . $siteDomain)) {
                $ok = true;
                break;
            }
        }
        if ($ok) {
            $out[] = $mb;
        }
    }
    return $out;
}

/**
 * Host-wide skills (DNS zones, PHP defaults, accounts list): admin only.
 *
 * @return array{ok:bool,error?:string}
 */
function mra_scope_require_admin_for_host(array $actor = null)
{
    if ($actor === null) {
        $actor = mra_scope_actor();
    }
    if ((string) ($actor['username'] ?? '') === '') {
        return ['ok' => false, 'error' => 'Sign in required.'];
    }
    if (empty($actor['is_admin'])) {
        return [
            'ok' => false,
            'error' => 'This host-wide data requires a panel owner/admin role (same as the matching panel page).',
        ];
    }
    return ['ok' => true];
}

/**
 * Deny reading another tenant's home tree. Allowed only under actor domains or admin.
 *
 * @return bool
 */
function mra_scope_path_allowed($path, array $actor = null)
{
    if ($actor === null) {
        $actor = mra_scope_actor();
    }
    $path = str_replace('\\', '/', (string) $path);
    $path = preg_replace('#/+#', '/', $path);
    if ($path === '' || strpos($path, '..') !== false) {
        return false;
    }
    if (!empty($actor['is_admin'])) {
        return true;
    }
    // Only /home/<domain>/... for allowed domains.
    if (!preg_match('#^/home/([^/]+)(/.*)?$#', $path, $m)) {
        return false;
    }
    $homeKey = strtolower(trim($m[1]));
    $allowed = mra_scope_allowed_domains($actor);
    if (isset($allowed[$homeKey])) {
        return true;
    }
    // Subdomain homes may be nested: /home/<parent>/<sub.fqdn>/
    foreach ($allowed as $domain => $_) {
        if ($homeKey === $domain || strpos($domain, $homeKey . '.') === 0) {
            return true;
        }
        if (strpos($path, '/home/' . $domain . '/') === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Attach scope metadata to skill responses (for debugging / UI; no secrets).
 *
 * @param array<string,mixed> $result
 * @return array<string,mixed>
 */
function mra_scope_annotate(array $result, array $actor = null)
{
    if ($actor === null) {
        $actor = mra_scope_actor();
    }
    $result['scope'] = [
        'username' => (string) ($actor['username'] ?? ''),
        'role' => (string) ($actor['role'] ?? 'user'),
        'is_admin' => !empty($actor['is_admin']),
        'isolation' => 'per_user',
    ];
    return $result;
}
