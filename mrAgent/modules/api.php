<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * JSON API router for ?api=...
 *
 * @param array<string,mixed> $cfg
 */
function mra_api_dispatch($api, array $cfg)
{
    $api = strtolower(trim((string) $api));
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($api === 'health') {
        mra_json(['ok' => true, 'plugin' => 'mrAgent', 'version' => MRA_VERSION]);
    }

    if ($api === 'csrf') {
        mra_session_start();
        mra_json(['ok' => true, 'csrf' => mra_csrf_token()]);
    }

    if ($api === 'login' && $method === 'POST') {
        $body = mra_body_json();
        if (!mra_csrf_check(isset($body['csrf']) ? $body['csrf'] : null) && !mra_csrf_check()) {
            mra_json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        $user = isset($body['username']) ? $body['username'] : mra_post('username');
        $pass = isset($body['password']) ? $body['password'] : mra_post('password');
        list($ok, $msg) = mra_login($user, $pass, $cfg);
        if (!$ok) {
            mra_json(['ok' => false, 'error' => $msg], 401);
        }
        if (!empty($body['package_id'])) {
            $_SESSION['mra_package'] = strtolower(trim((string) $body['package_id']));
        }
        list($aclOk, $aclErr) = mra_acl_allows($cfg);
        mra_json([
            'ok' => true,
            'message' => $msg,
            'user' => mra_user(),
            'role' => mra_role(),
            'acl_ok' => $aclOk,
            'acl_error' => $aclOk ? '' : $aclErr,
            'csrf' => mra_csrf_token(),
        ]);
    }

    if ($api === 'logout' && $method === 'POST') {
        if (!mra_csrf_check()) {
            $body = mra_body_json();
            if (!mra_csrf_check(isset($body['csrf']) ? $body['csrf'] : null)) {
                mra_json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
            }
        }
        mra_logout();
        mra_json(['ok' => true]);
    }

    if (mra_user() === '') {
        mra_json(['ok' => false, 'error' => 'Sign in required'], 401);
    }

    list($aclOk, $aclErr) = mra_acl_allows($cfg);
    if (!$aclOk && $api !== 'me') {
        mra_json(['ok' => false, 'error' => $aclErr], 403);
    }

    if ($api === 'me') {
        mra_json([
            'ok' => true,
            'user' => mra_user(),
            'role' => mra_role(),
            'acl_ok' => $aclOk,
            'acl_error' => $aclOk ? '' : $aclErr,
            'default_provider' => (string) ($cfg['default_provider'] ?? 'free'),
            'allow_user_keys' => !empty($cfg['allow_user_keys']),
            'is_owner' => mra_is_owner(),
            'providers' => mra_provider_status($cfg, mra_user()),
            'csrf' => mra_csrf_token(),
            'version' => MRA_VERSION,
        ]);
    }

    if ($api === 'providers') {
        mra_json(['ok' => true, 'providers' => mra_provider_status($cfg, mra_user())]);
    }

    if ($api === 'chat' && $method === 'POST') {
        $body = mra_body_json();
        if (!mra_csrf_check(isset($body['csrf']) ? $body['csrf'] : null)) {
            mra_json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        $message = isset($body['message']) ? (string) $body['message'] : '';
        $provider = isset($body['provider']) ? (string) $body['provider'] : '';
        $model = isset($body['model']) ? (string) $body['model'] : '';
        $result = mra_chat_handle($message, $provider, $model, $cfg, mra_user());
        $code = !empty($result['ok']) ? 200 : 400;
        mra_json($result, $code);
    }

    if ($api === 'keys' && $method === 'GET') {
        if (!mra_can_manage_keys($cfg)) {
            mra_json(['ok' => false, 'error' => 'Key management disabled'], 403);
        }
        $store = mra_keys_store($cfg['domain'] ?? null);
        $user = mra_user();
        $mine = isset($store['users'][$user]) && is_array($store['users'][$user]) ? $store['users'][$user] : [];
        $masked = [];
        foreach (['openai', 'anthropic', 'custom', 'local'] as $p) {
            $masked[$p] = [
                'set' => !empty($mine[$p]),
                'mask' => !empty($mine[$p]) ? mra_mask_key($mine[$p]) : '',
            ];
        }
        $hostMasked = [];
        if (mra_is_owner()) {
            foreach (['openai', 'anthropic', 'custom', 'local', 'custom_base_url'] as $p) {
                $hostMasked[$p] = [
                    'set' => !empty($store['host'][$p]),
                    'mask' => ($p === 'custom_base_url')
                        ? (string) ($store['host'][$p] ?? '')
                        : (!empty($store['host'][$p]) ? mra_mask_key($store['host'][$p]) : ''),
                ];
            }
        }
        mra_json(['ok' => true, 'user_keys' => $masked, 'host_keys' => $hostMasked]);
    }

    if ($api === 'keys' && $method === 'POST') {
        if (!mra_can_manage_keys($cfg) && !mra_is_owner()) {
            mra_json(['ok' => false, 'error' => 'Key management disabled'], 403);
        }
        $body = mra_body_json();
        if (!mra_csrf_check(isset($body['csrf']) ? $body['csrf'] : null)) {
            mra_json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        $store = mra_keys_store($cfg['domain'] ?? null);
        $scope = isset($body['scope']) ? strtolower((string) $body['scope']) : 'user';
        $provider = strtolower(trim((string) ($body['provider'] ?? '')));
        $value = isset($body['value']) ? trim((string) $body['value']) : '';
        $clear = !empty($body['clear']);
        $allowedProviders = ['openai', 'anthropic', 'custom', 'local', 'custom_base_url'];
        if (!in_array($provider, $allowedProviders, true)) {
            mra_json(['ok' => false, 'error' => 'Invalid provider'], 400);
        }
        if ($scope === 'host') {
            if (!mra_is_owner()) {
                mra_json(['ok' => false, 'error' => 'Owner only'], 403);
            }
            if ($clear) {
                unset($store['host'][$provider]);
            } elseif ($value !== '') {
                $store['host'][$provider] = $value;
            }
        } else {
            if ($provider === 'custom_base_url') {
                mra_json(['ok' => false, 'error' => 'custom_base_url is host-only'], 400);
            }
            $user = mra_user();
            if (!isset($store['users'][$user]) || !is_array($store['users'][$user])) {
                $store['users'][$user] = [];
            }
            if ($clear) {
                unset($store['users'][$user][$provider]);
            } elseif ($value !== '') {
                $store['users'][$user][$provider] = $value;
            }
        }
        if (!mra_keys_save($store, $cfg['domain'] ?? null)) {
            mra_json(['ok' => false, 'error' => 'Could not save keys (check /var/lib/cpn/mr-agent permissions)'], 500);
        }
        mra_json(['ok' => true, 'message' => 'Saved']);
    }

    if ($api === 'owner-settings' && $method === 'POST') {
        if (!mra_is_owner()) {
            mra_json(['ok' => false, 'error' => 'Owner only'], 403);
        }
        $body = mra_body_json();
        if (!mra_csrf_check(isset($body['csrf']) ? $body['csrf'] : null)) {
            mra_json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        $next = [
            'plugin_enabled' => !empty($body['plugin_enabled']),
            'visibility' => strtolower(trim((string) ($body['visibility'] ?? 'admins_only'))),
            'package_ids' => trim((string) ($body['package_ids'] ?? '')),
            'allow_user_keys' => !empty($body['allow_user_keys']),
            'default_provider' => strtolower(trim((string) ($body['default_provider'] ?? 'free'))),
            'rate_limit_per_hour' => max(1, (int) ($body['rate_limit_per_hour'] ?? 60)),
            'custom_base_url' => trim((string) ($body['custom_base_url'] ?? '')),
            'local_base_url' => trim((string) ($body['local_base_url'] ?? 'http://127.0.0.1:11434/v1')),
            'local_model' => trim((string) ($body['local_model'] ?? 'llama3.2:1b')),
        ];
        if (!in_array($next['visibility'], ['admins_only', 'all_authenticated', 'packages'], true)) {
            mra_json(['ok' => false, 'error' => 'Invalid visibility'], 400);
        }
        if (!empty($body['access_password'])) {
            $next['access_password'] = (string) $body['access_password'];
        }
        if (!mra_save_owner_settings($next, $cfg['domain'] ?? null)) {
            mra_json(['ok' => false, 'error' => 'Could not save settings'], 500);
        }
        // Also update config.php access password when provided and file is writable.
        if (!empty($body['access_password']) && is_file(MRA_ROOT . '/config.php') && is_writable(MRA_ROOT . '/config.php')) {
            // Prefer var/lib settings; config.php remains for bootstrap.
        }
        mra_json(['ok' => true, 'message' => 'Owner settings saved']);
    }

    if ($api === 'search' && $method === 'GET') {
        $q = mra_get('q');
        mra_json(['ok' => true, 'results' => mra_search_corpus($q, 10)]);
    }

    if ($api === 'skills' && $method === 'GET') {
        mra_json([
            'ok' => true,
            'mcp' => 'Panel-wide tool protocol used by Mr Agent skills',
            'skills' => mra_skills_catalog(),
            'tools' => array_map(function ($t) {
                return [
                    'name' => $t['name'],
                    'description' => $t['description'],
                ];
            }, mra_tool_definitions()),
        ]);
    }

    if ($api === 'mcp' && $method === 'POST') {
        $body = mra_body_json();
        if (!mra_csrf_check(isset($body['csrf']) ? $body['csrf'] : null)) {
            mra_json(['ok' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        $action = strtolower(trim((string) ($body['action'] ?? 'list_tools')));
        if ($action === 'list_tools' || $action === 'tools/list') {
            mra_json([
                'ok' => true,
                'protocol' => 'mcp-style',
                'tools' => mra_tool_definitions(),
                'skills' => mra_skills_catalog(),
            ]);
        }
        if ($action === 'call_tool' || $action === 'tools/call') {
            $tool = isset($body['name']) ? (string) $body['name'] : (string) ($body['tool'] ?? '');
            $args = isset($body['arguments']) && is_array($body['arguments']) ? $body['arguments'] : [];
            if ($tool === '' && isset($body['params']['name'])) {
                $tool = (string) $body['params']['name'];
                if (isset($body['params']['arguments']) && is_array($body['params']['arguments'])) {
                    $args = $body['params']['arguments'];
                }
            }
            $result = mra_tool_execute($tool, $args, $cfg, mra_user());
            $code = !empty($result['ok']) ? 200 : 400;
            mra_json(['ok' => !empty($result['ok']), 'result' => $result], $code);
        }
        mra_json(['ok' => false, 'error' => 'Unknown MCP action. Use list_tools or call_tool.'], 400);
    }

    mra_json(['ok' => false, 'error' => 'Unknown API'], 404);
}
