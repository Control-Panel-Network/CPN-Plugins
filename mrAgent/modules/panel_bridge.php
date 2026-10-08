#!/usr/bin/env php
<?php
/**
 * CLI bridge for CPN Panel float chat.
 * Reads one JSON object from stdin; writes one JSON object to stdout.
 *
 * Invoked only by the panel (trusted local). Does not accept HTTP.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

define('MRA_INIT', true);
require_once __DIR__ . '/bootstrap.php';

/**
 * @return array<string,mixed>
 */
function mra_bridge_read_stdin()
{
    $raw = stream_get_contents(STDIN);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Establish a synthetic session for ACL / rate-limit / keys keyed by panel username.
 *
 * @param array<string,mixed> $req
 */
function mra_bridge_impersonate(array $req)
{
    mra_session_start();
    $username = strtolower(trim((string) ($req['username'] ?? '')));
    if ($username === '' || !preg_match('/^[a-z0-9_.\-]{1,64}$/', $username)) {
        return [false, 'Invalid username'];
    }
    $role = strtolower(trim((string) ($req['role'] ?? 'user')));
    if (!in_array($role, ['owner', 'user'], true)) {
        $role = 'user';
    }
    // Panel admin is treated as owner for inventory skills.
    if ($role === 'owner' || in_array($username, ['owner', 'admin', 'cpnowner'], true)) {
        $role = 'owner';
    }
    $_SESSION['mra_user'] = $username;
    $_SESSION['mra_role'] = $role;
    $_SESSION['mra_at'] = mra_now_unix();
    $_SESSION['mra_bridge'] = 1;
    $pkg = strtolower(trim((string) ($req['package_id'] ?? '')));
    if ($pkg !== '') {
        $_SESSION['mra_package'] = $pkg;
    }
    return [true, ''];
}

function mra_bridge_out(array $data, $code = 0)
{
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "\n";
    exit((int) $code);
}

try {
    $req = mra_bridge_read_stdin();
    $action = strtolower(trim((string) ($req['action'] ?? 'chat')));
    $domainHint = strtolower(trim((string) ($req['domain'] ?? '')));
    if ($domainHint !== '') {
        // Prefer domain from panel so secrets resolve under /var/lib/cpn/mr-agent/<domain>.
        if (!is_dir(MRA_ROOT . '/data')) {
            @mkdir(MRA_ROOT . '/data', 0700, true);
        }
        @file_put_contents(MRA_ROOT . '/data/domain.txt', $domainHint . "\n");
        @chmod(MRA_ROOT . '/data/domain.txt', 0600);
    }

    $cfg = mra_boot();
    if ($domainHint !== '') {
        $cfg['domain'] = $domainHint;
    }

    list($okImp, $impErr) = mra_bridge_impersonate($req);
    if (!$okImp) {
        mra_bridge_out(['ok' => false, 'error' => $impErr], 1);
    }

    list($aclOk, $aclErr) = mra_acl_allows($cfg, isset($req['package_id']) ? $req['package_id'] : null);
    if (!$aclOk) {
        mra_bridge_out(['ok' => false, 'error' => $aclErr], 1);
    }

    if ($action === 'ping') {
        mra_bridge_out([
            'ok' => true,
            'plugin' => 'mrAgent',
            'version' => MRA_VERSION,
            'user' => mra_user(),
            'role' => mra_role(),
        ]);
    }

    if ($action === 'prune') {
        $pruned = mra_prune_storage($cfg, $cfg['domain'] ?? null);
        mra_bridge_out([
            'ok' => true,
            'pruned' => $pruned,
            'version' => MRA_VERSION,
        ]);
    }

    if ($action !== 'chat') {
        mra_bridge_out(['ok' => false, 'error' => 'Unknown bridge action'], 1);
    }

    $message = (string) ($req['message'] ?? '');
    $provider = (string) ($req['provider'] ?? 'free');
    $model = (string) ($req['model'] ?? '');
    $conversationId = (string) ($req['conversation_id'] ?? '');
    $result = mra_chat_handle($message, $provider, $model, $cfg, mra_user(), $conversationId);
    if (empty($result['ok'])) {
        mra_bridge_out([
            'ok' => false,
            'error' => isset($result['error']) ? (string) $result['error'] : 'Chat failed',
        ], 1);
    }
    mra_bridge_out([
        'ok' => true,
        'reply' => isset($result['reply']) ? (string) $result['reply'] : '',
        'provider' => isset($result['provider']) ? (string) $result['provider'] : $provider,
        'tools_used' => isset($result['tools_used']) ? $result['tools_used'] : [],
        'conversation_id' => isset($result['conversation_id']) ? (string) $result['conversation_id'] : '',
        'version' => MRA_VERSION,
    ]);
} catch (Throwable $e) {
    mra_log('panel_bridge_failed', ['error' => mra_redact($e->getMessage())]);
    mra_bridge_out(['ok' => false, 'error' => 'Mr Agent bridge failed'], 1);
}
