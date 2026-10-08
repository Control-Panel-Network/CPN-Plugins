<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_plugins_run($tool, array $args, array $cfg, $username)
{
    $actor = mra_scope_actor();
    if ($actor['username'] === '') {
        return ['ok' => false, 'error' => 'Sign in required.'];
    }
    // Host package inventory is admin-only; site plugin lists will use site scope later.
    $gate = mra_scope_require_admin_for_host($actor);
    if (empty($gate['ok'])) {
        return mra_scope_annotate([
            'ok' => true,
            'tool' => strtolower(trim((string) $tool)),
            'stub' => true,
            'planned' => ['list_installed_plugins', 'list_host_packages'],
            'note' => 'Host package catalog requires admin. Site plugin lists will follow your website scope. Use Help for /plugins.',
            'allowed_domains' => array_keys(mra_scope_allowed_domains($actor)),
        ], $actor);
    }
    return mra_scope_annotate([
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['list_installed_plugins', 'list_host_packages'],
        'note' => 'Plugins skill stub (admin). Use Help skill for /plugins Store and Installed.',
    ], $actor);
}

return [
    'id' => 'plugins',
    'name' => 'Plugins',
    'description' => 'CPN Plugins / Host packages (stub). Host catalog is admin-only; site lists will use site scope.',
    'area' => 'plugins',
    'free' => true,
    'authz' => 'any',
    'status' => 'stub',
    'run' => 'mra_skill_plugins_run',
    'tools' => [
        [
            'name' => 'plugins_skill_status',
            'description' => 'Report Plugins skill stub status (scoped; host catalog admin-only).',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
