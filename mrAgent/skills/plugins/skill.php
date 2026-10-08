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
    return [
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['list_installed_plugins', 'list_host_packages'],
        'note' => 'Plugins skill stub. Use Help skill for /plugins Store and Installed.',
    ];
}

return [
    'id' => 'plugins',
    'name' => 'Plugins',
    'description' => 'CPN Plugins / Host packages (stub). Planned: installed catalog and host package status.',
    'area' => 'plugins',
    'free' => true,
    'authz' => 'owner',
    'status' => 'stub',
    'run' => 'mra_skill_plugins_run',
    'tools' => [
        [
            'name' => 'plugins_skill_status',
            'description' => 'Report Plugins skill stub status and planned MCP tools.',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
