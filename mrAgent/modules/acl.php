<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Check whether the signed-in user may use Mr Agent for this site.
 *
 * @param array<string,mixed> $cfg
 * @param string|null $packageId Optional CPN package id for the user (from session or form).
 */
function mra_acl_allows(array $cfg, $packageId = null)
{
    if (empty($cfg['plugin_enabled'])) {
        return [false, 'Mr Agent is disabled for this site.'];
    }

    $user = mra_user();
    if ($user === '') {
        return [false, 'Sign in required.'];
    }

    $visibility = strtolower(trim((string) ($cfg['visibility'] ?? 'admins_only')));
    $role = mra_role();

    if ($visibility === 'admins_only') {
        if ($role === 'owner' || in_array($user, ['owner', 'admin', 'cpnowner'], true)) {
            return [true, ''];
        }
        return [false, 'Mr Agent is limited to administrators on this site.'];
    }

    if ($visibility === 'all_authenticated') {
        return [true, ''];
    }

    if ($visibility === 'packages') {
        $raw = (string) ($cfg['package_ids'] ?? '');
        $allowed = array_filter(array_map(function ($s) {
            return strtolower(trim($s));
        }, explode(',', $raw)));
        if (empty($allowed)) {
            return [false, 'No packages are allowed. Ask the owner to set package_ids.'];
        }
        $pkg = strtolower(trim((string) ($packageId !== null ? $packageId : (isset($_SESSION['mra_package']) ? $_SESSION['mra_package'] : ''))));
        if ($pkg !== '' && in_array($pkg, $allowed, true)) {
            return [true, ''];
        }
        // Owners always pass package gate for administration.
        if ($role === 'owner') {
            return [true, ''];
        }
        return [false, 'Your package is not allowed to use Mr Agent.'];
    }

    return [false, 'Unknown visibility setting.'];
}

function mra_can_manage_keys(array $cfg)
{
    if (mra_is_owner()) {
        return true;
    }
    return !empty($cfg['allow_user_keys']);
}
