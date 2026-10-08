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
    $actor = mra_scope_actor();
    $gate = mra_scope_require_admin_for_host($actor);
    if (empty($gate['ok'])) {
        return array_merge(['tool' => strtolower(trim((string) $tool))], $gate);
    }
    return mra_scope_annotate([
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['list_accounts'],
        'note' => 'Accounts skill stub (admin only). Never expose passwords or MFA secrets. Use Help for /account/users.',
    ], $actor);
}

return [
    'id' => 'accounts',
    'name' => 'Accounts',
    'description' => 'CPN users and accounts (stub). Admin only; never secrets/MFA.',
    'area' => 'accounts',
    'free' => true,
    'authz' => 'any',
    'status' => 'stub',
    'run' => 'mra_skill_accounts_run',
    'tools' => [
        [
            'name' => 'accounts_skill_status',
            'description' => 'Report Accounts skill stub status (admin only).',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
