<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Provider API keys and per-user keys. Never log contents.
 *
 * @return array{host: array<string,string>, users: array<string,array<string,string>>}
 */
function mra_keys_store($domain = null)
{
    $dir = mra_var_lib_dir($domain);
    mra_safe_mkdir($dir, 0700);
    $path = $dir . '/keys.json';
    if (!is_file($path)) {
        return ['host' => [], 'users' => []];
    }
    $raw = @file_get_contents($path);
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        return ['host' => [], 'users' => []];
    }
    return [
        'host' => isset($data['host']) && is_array($data['host']) ? $data['host'] : [],
        'users' => isset($data['users']) && is_array($data['users']) ? $data['users'] : [],
    ];
}

function mra_keys_save(array $store, $domain = null)
{
    $dir = mra_var_lib_dir($domain);
    if (!mra_safe_mkdir($dir, 0700)) {
        return false;
    }
    $path = $dir . '/keys.json';
    $payload = [
        'host' => isset($store['host']) && is_array($store['host']) ? $store['host'] : [],
        'users' => isset($store['users']) && is_array($store['users']) ? $store['users'] : [],
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $ok = @file_put_contents($path, $json, LOCK_EX) !== false;
    if ($ok) {
        @chmod($path, 0600);
    }
    return $ok;
}

/**
 * Resolve a provider API key for the active user.
 */
function mra_resolve_api_key($provider, $username, array $cfg)
{
    $provider = strtolower(trim((string) $provider));
    $username = strtolower(trim((string) $username));
    $store = mra_keys_store($cfg['domain'] ?? null);

    if ($username !== '' && !empty($store['users'][$username][$provider])) {
        return (string) $store['users'][$username][$provider];
    }
    if (!empty($store['host'][$provider])) {
        return (string) $store['host'][$provider];
    }

    $map = [
        'openai' => 'openai_api_key',
        'anthropic' => 'anthropic_api_key',
        'custom' => 'custom_api_key',
        'local' => 'local_api_key',
    ];
    if (isset($map[$provider]) && !empty($cfg[$map[$provider]])) {
        return (string) $cfg[$map[$provider]];
    }
    return '';
}

function mra_mask_key($key)
{
    $key = (string) $key;
    $len = strlen($key);
    if ($len <= 8) {
        return $len > 0 ? str_repeat('*', $len) : '';
    }
    return substr($key, 0, 3) . str_repeat('*', max(4, $len - 7)) . substr($key, -4);
}

function mra_save_owner_settings(array $settings, $domain = null)
{
    $dir = mra_var_lib_dir($domain);
    if (!mra_safe_mkdir($dir, 0700)) {
        return false;
    }
    $path = $dir . '/settings.json';
    $allowed = [
        'plugin_enabled', 'visibility', 'package_ids', 'allow_user_keys',
        'default_provider', 'rate_limit_per_hour', 'access_password',
        'custom_base_url', 'local_base_url', 'local_model',
        'local_only_mode', 'local_allow_lan',
        'max_history_messages', 'max_stored_conversations', 'chat_retention_days',
        'max_chat_disk_mb', 'max_tokens_per_reply', 'max_message_length',
        'concurrent_requests', 'local_timeout_seconds', 'local_max_response_bytes',
        'max_upload_bytes',
    ];
    $out = [];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $settings)) {
            $out[$k] = $settings[$k];
        }
    }
    $json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $ok = @file_put_contents($path, $json, LOCK_EX) !== false;
    if ($ok) {
        @chmod($path, 0600);
    }
    return $ok;
}
