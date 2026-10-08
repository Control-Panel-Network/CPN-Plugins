<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Read-only hosting packages scoped to the authenticated CPN user.
 *
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_packages_run($tool, array $args, array $cfg, $username)
{
    $tool = strtolower(trim((string) $tool));
    if ($tool !== 'list_packages') {
        return ['ok' => false, 'error' => 'Unknown packages tool: ' . $tool];
    }

    $actor = mra_scope_actor();
    if ($actor['username'] === '') {
        return ['ok' => false, 'error' => 'Sign in required.'];
    }

    $path = mra_cpn_data_dir() . '/packages.json';
    if (!is_file($path) || !is_readable($path)) {
        return mra_scope_annotate([
            'ok' => true,
            'tool' => 'list_packages',
            'packages' => [],
            'note' => 'packages.json not readable at ' . $path,
        ], $actor);
    }

    $raw = (string) @file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Could not parse packages.json'];
    }

    $list = [];
    if (isset($data['packages']) && is_array($data['packages'])) {
        $list = $data['packages'];
    } elseif (array_keys($data) === range(0, count($data) - 1)) {
        $list = $data;
    }

    $q = isset($args['query']) ? strtolower(trim((string) $args['query'])) : '';
    $normalized = [];
    foreach ($list as $pkg) {
        if (!is_array($pkg)) {
            continue;
        }
        $row = [
            'id' => (string) ($pkg['id'] ?? ''),
            'name' => (string) ($pkg['name'] ?? ''),
            'domains' => isset($pkg['domains']) ? $pkg['domains'] : null,
            'emails' => isset($pkg['emails']) ? $pkg['emails'] : null,
            'databases' => isset($pkg['databases']) ? $pkg['databases'] : null,
            'disk_mb' => isset($pkg['disk_mb']) ? $pkg['disk_mb'] : null,
            'bandwidth_mb' => isset($pkg['bandwidth_mb']) ? $pkg['bandwidth_mb'] : null,
            'ftp_accounts' => isset($pkg['ftp_accounts']) ? $pkg['ftp_accounts'] : null,
        ];
        if ($row['id'] === '' && $row['name'] === '') {
            continue;
        }
        if ($q !== '') {
            $hay = strtolower($row['id'] . ' ' . $row['name']);
            if (strpos($hay, $q) === false) {
                continue;
            }
        }
        $normalized[] = $row;
    }

    $out = mra_scope_filter_packages($normalized, $actor);

    return mra_scope_annotate([
        'ok' => true,
        'tool' => 'list_packages',
        'count' => count($out),
        'packages' => $out,
        'note' => 'Read-only. Non-admins see only their assigned package. Admins see all packages. Limit -1 means unlimited; 0 means none.',
    ], $actor);
}

return [
    'id' => 'packages',
    'name' => 'Packages',
    'description' => 'Read-only hosting packages scoped to the signed-in user (assigned package). Admins see all.',
    'area' => 'packages',
    'free' => true,
    'authz' => 'any',
    'status' => 'active',
    'run' => 'mra_skill_packages_run',
    'tools' => [
        [
            'name' => 'list_packages',
            'description' => 'List CPN hosting packages visible to the current user (read-only). Optional query filters by id/name.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Optional filter text'],
                ],
            ],
        ],
    ],
];
