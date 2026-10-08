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
    return [
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['php_default_version', 'list_php_extensions'],
        'note' => 'PHP skill stub. Use Help skill for /server/php paths.',
    ];
}

return [
    'id' => 'php',
    'name' => 'PHP',
    'description' => 'CPN PHP host tooling (stub). Planned: default version and extensions (read-only first).',
    'area' => 'php',
    'free' => true,
    'authz' => 'owner',
    'status' => 'stub',
    'run' => 'mra_skill_php_run',
    'tools' => [
        [
            'name' => 'php_skill_status',
            'description' => 'Report PHP skill stub status and planned MCP tools.',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
