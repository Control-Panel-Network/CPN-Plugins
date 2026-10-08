<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Resolve site domain for secret paths.
 */
function mra_detect_domain(array $cfg = [])
{
    if (!empty($cfg['domain'])) {
        return strtolower(trim((string) $cfg['domain']));
    }
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
    $host = preg_replace('/:\d+$/', '', $host);
    if ($host !== '' && $host !== 'localhost' && $host !== '127.0.0.1') {
        return $host;
    }
    $marker = MRA_ROOT . '/data/domain.txt';
    if (is_file($marker)) {
        $d = strtolower(trim((string) @file_get_contents($marker)));
        if ($d !== '') {
            return $d;
        }
    }
    return 'local';
}

function mra_var_lib_dir($domain = null)
{
    $domain = $domain !== null ? strtolower(trim((string) $domain)) : mra_detect_domain();
    $domain = preg_replace('/[^a-z0-9.\-_]/', '', $domain) ?: 'local';
    return '/var/lib/cpn/mr-agent/' . $domain;
}

/**
 * @return array<string,mixed>
 */
function mra_config()
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }

    $defaults = [
        'access_password' => '',
        'openai_api_key' => '',
        'anthropic_api_key' => '',
        'custom_api_key' => '',
        'custom_base_url' => '',
        'local_base_url' => 'http://127.0.0.1:11434/v1',
        'local_api_key' => '',
        'local_model' => 'llama3.2:1b',
        'domain' => '',
        'plugin_enabled' => true,
        'visibility' => 'admins_only',
        'package_ids' => '',
        'allow_user_keys' => true,
        'default_provider' => 'free',
        'rate_limit_per_hour' => 60,
    ];

    $loaded = [];
    $candidates = [
        MRA_ROOT . '/config.php',
    ];
    $domainHint = '';
    if (is_file(MRA_ROOT . '/data/domain.txt')) {
        $domainHint = strtolower(trim((string) @file_get_contents(MRA_ROOT . '/data/domain.txt')));
    }
    if ($domainHint !== '') {
        $candidates[] = mra_var_lib_dir($domainHint) . '/config.php';
    }
    $candidates[] = '/var/lib/cpn/mr-agent/config.php';

    foreach ($candidates as $path) {
        if (is_file($path)) {
            $data = require $path;
            if (is_array($data)) {
                $loaded = array_merge($loaded, $data);
            }
        }
    }

    $cfg = array_merge($defaults, $loaded);
    $domain = mra_detect_domain($cfg);
    $cfg['domain'] = $domain;

    // Overlay panel settings.json when present.
    $panelSettings = mra_load_panel_settings($domain);
    foreach ([
        'enabled' => 'plugin_enabled',
        'visibility' => 'visibility',
        'package_ids' => 'package_ids',
        'allow_user_keys' => 'allow_user_keys',
        'default_provider' => 'default_provider',
        'rate_limit_per_hour' => 'rate_limit_per_hour',
    ] as $from => $to) {
        if (!array_key_exists($from, $panelSettings)) {
            continue;
        }
        $val = $panelSettings[$from];
        if ($to === 'plugin_enabled' || $to === 'allow_user_keys') {
            $cfg[$to] = ($val === '1' || $val === 1 || $val === true || $val === 'true' || $val === 'on');
        } elseif ($to === 'rate_limit_per_hour') {
            $cfg[$to] = max(1, (int) $val);
        } else {
            $cfg[$to] = (string) $val;
        }
    }

    // Overlay owner settings from var/lib.
    $ownerPath = mra_var_lib_dir($domain) . '/settings.json';
    if (is_file($ownerPath)) {
        $raw = @file_get_contents($ownerPath);
        $owner = json_decode((string) $raw, true);
        if (is_array($owner)) {
            foreach ($owner as $k => $v) {
                if (array_key_exists($k, $defaults)) {
                    $cfg[$k] = $v;
                }
            }
        }
    }

    return $cfg;
}

/**
 * Read CPN panel plugin settings.json for this site install.
 *
 * @return array<string,string>
 */
function mra_load_panel_settings($domain)
{
    $path = MRA_ROOT . '/settings.json';
    if (!is_file($path)) {
        // When installed under host-plugins copy, also try site plugins path via domain marker.
        $alt = '/home/' . $domain . '/plugins/mrAgent/settings.json';
        if (is_file($alt)) {
            $path = $alt;
        } else {
            return [];
        }
    }
    $raw = @file_get_contents($path);
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return [];
    }
    $fields = isset($data['fields']) && is_array($data['fields']) ? $data['fields'] : [];
    $out = [];
    foreach ($fields as $k => $v) {
        $out[(string) $k] = (string) $v;
    }
    return $out;
}
