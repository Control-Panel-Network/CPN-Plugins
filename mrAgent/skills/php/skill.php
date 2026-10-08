<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_php_run($tool, array $args, array $cfg, $username)
{
    $actor = mra_scope_actor();
    $gate = mra_scope_require_admin_for_host($actor);
    if (empty($gate['ok'])) {
        return array_merge(['tool' => strtolower(trim((string) $tool))], $gate);
    }
    return mra_scope_annotate([
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['php_default_version', 'list_php_extensions'],
        'note' => 'PHP skill stub (admin/owner only for host defaults). Use Help skill for /server/php paths.',
    ], $actor);
}

return [
    'id' => 'php',
    'name' => 'PHP',
    'description' => 'CPN PHP host tooling (stub). Host defaults require admin (same as panel PHP pages).',
    'area' => 'php',
    'free' => true,
    'authz' => 'any',
    'status' => 'stub',
    'run' => 'mra_skill_php_run',
    'tools' => [
        [
            'name' => 'php_skill_status',
            'description' => 'Report PHP skill stub status and planned MCP tools (admin for host-wide).',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
