<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Stub: Email area skill (accounts, DKIM, webmail links later).
 *
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_email_run($tool, array $args, array $cfg, $username)
{
    return [
        'ok' => true,
        'tool' => strtolower(trim((string) $tool)),
        'stub' => true,
        'planned' => ['list_mailboxes', 'list_email_domains', 'dkim_status'],
        'note' => 'Email skill stub. Use Help skill for Email menu paths (/email, /email/accounts).',
    ];
}

return [
    'id' => 'email',
    'name' => 'Email',
    'description' => 'CPN Email area (stub). Planned: mailboxes, domains, DKIM status (read-only first).',
    'area' => 'email',
    'free' => true,
    'authz' => 'owner',
    'status' => 'stub',
    'run' => 'mra_skill_email_run',
    'tools' => [
        [
            'name' => 'email_skill_status',
            'description' => 'Report Email skill stub status and planned MCP tools.',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
