<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_dns_run($tool, array $args, array $cfg, $username)
{
    return [
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['list_dns_zones', 'list_dns_records'],
        'note' => 'DNS skill stub. Use Help skill for /server/dns/zones and Cloudflare manage paths.',
    ];
}

return [
    'id' => 'dns',
    'name' => 'DNS',
    'description' => 'CPN DNS area (stub). Planned: zones and records (read-only first).',
    'area' => 'dns',
    'free' => true,
    'authz' => 'owner',
    'status' => 'stub',
    'run' => 'mra_skill_dns_run',
    'tools' => [
        [
            'name' => 'dns_skill_status',
            'description' => 'Report DNS skill stub status and planned MCP tools.',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
