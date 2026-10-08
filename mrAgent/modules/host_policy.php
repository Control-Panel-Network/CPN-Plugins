<?php
/**
 * Host-level Mr Agent owner policy (read by site UI; edited in CPN Panel Host settings).
 *
 * File: /var/lib/cpn/mr-agent/host-policy.json
 * - allow_host_chat (default true): panel bubble / /plugins/mr-agent
 * - allow_site_install (default false): Store Site Install for mrAgent
 */
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * @return array{allow_host_chat:bool,allow_site_install:bool}
 */
function mra_host_policy_defaults()
{
    return [
        'allow_host_chat' => true,
        'allow_site_install' => false,
    ];
}

/**
 * @return array{allow_host_chat:bool,allow_site_install:bool}
 */
function mra_load_host_policy()
{
    $path = '/var/lib/cpn/mr-agent/host-policy.json';
    $defaults = mra_host_policy_defaults();
    if (!is_file($path)) {
        return $defaults;
    }
    $raw = @file_get_contents($path);
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return $defaults;
    }
    return [
        'allow_host_chat' => array_key_exists('allow_host_chat', $data)
            ? !empty($data['allow_host_chat'])
            : $defaults['allow_host_chat'],
        'allow_site_install' => array_key_exists('allow_site_install', $data)
            ? !empty($data['allow_site_install'])
            : $defaults['allow_site_install'],
    ];
}

/**
 * Persist host policy (panel owner callers only).
 *
 * @param array{allow_host_chat?:bool,allow_site_install?:bool} $policy
 * @return bool
 */
function mra_save_host_policy(array $policy)
{
    $dir = '/var/lib/cpn/mr-agent';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return false;
    }
    $out = [
        'allow_host_chat' => !empty($policy['allow_host_chat']),
        'allow_site_install' => !empty($policy['allow_site_install']),
    ];
    $json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $path = $dir . '/host-policy.json';
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $json . "\n") === false) {
        return false;
    }
    @chmod($tmp, 0600);
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    @chmod($path, 0600);
    return true;
}

function mra_host_policy_allow_host_chat()
{
    $p = mra_load_host_policy();
    return !empty($p['allow_host_chat']);
}

function mra_host_policy_allow_site_install()
{
    $p = mra_load_host_policy();
    return !empty($p['allow_site_install']);
}
