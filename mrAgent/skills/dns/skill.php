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
    $actor = mra_scope_actor();
    $gate = mra_scope_require_admin_for_host($actor);
    if (empty($gate['ok'])) {
        return array_merge(['tool' => strtolower(trim((string) $tool))], $gate);
    }
    return mra_scope_annotate([
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['list_dns_zones', 'list_dns_records'],
        'note' => 'DNS skill stub (admin/owner only for host-wide zones). Use Help skill for /server/dns/zones and Cloudflare manage paths.',
    ], $actor);
}

return [
    'id' => 'dns',
    'name' => 'DNS',
    'description' => 'CPN DNS area (stub). Host-wide zones require admin; future record lists will follow site scope.',
    'area' => 'dns',
    'free' => true,
    'authz' => 'any',
    'status' => 'stub',
    'run' => 'mra_skill_dns_run',
    'tools' => [
        [
            'name' => 'dns_skill_status',
            'description' => 'Report DNS skill stub status and planned MCP tools (admin for host-wide).',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
