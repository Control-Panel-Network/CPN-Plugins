<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_accounts_run($tool, array $args, array $cfg, $username)
{
    return [
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['list_accounts'],
        'note' => 'Accounts skill stub. Never expose passwords or MFA secrets. Use Help for /account/users.',
    ];
}

return [
    'id' => 'accounts',
    'name' => 'Accounts',
    'description' => 'CPN users and accounts (stub). Planned: username/role list only (no secrets).',
    'area' => 'accounts',
    'free' => true,
    'authz' => 'owner',
    'status' => 'stub',
    'run' => 'mra_skill_accounts_run',
    'tools' => [
        [
            'name' => 'accounts_skill_status',
            'description' => 'Report Accounts skill stub status and planned MCP tools.',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
