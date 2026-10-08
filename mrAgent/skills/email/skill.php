<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Email skill: list mailboxes for domains in the actor's site scope.
 * Never returns SMTP/mailbox passwords.
 *
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_email_run($tool, array $args, array $cfg, $username)
{
    $tool = strtolower(trim((string) $tool));
    $actor = mra_scope_actor();
    if ($actor['username'] === '') {
        return ['ok' => false, 'error' => 'Sign in required.'];
    }

    if ($tool === 'email_skill_status') {
        return mra_scope_annotate([
            'ok' => true,
            'tool' => 'email_skill_status',
            'stub' => false,
            'tools' => ['list_mailboxes', 'email_skill_status'],
            'planned' => ['list_email_domains', 'dkim_status'],
            'note' => 'Mailboxes are filtered to domains you own or may manage. Passwords are never returned.',
        ], $actor);
    }

    if ($tool !== 'list_mailboxes') {
        return ['ok' => false, 'error' => 'Unknown email tool: ' . $tool];
    }

    $q = isset($args['query']) ? strtolower(trim((string) $args['query'])) : '';
    $limit = isset($args['limit']) ? (int) $args['limit'] : 100;
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 500) {
        $limit = 500;
    }

    $mailboxes = [];
    foreach (mra_scope_list_mailboxes($actor) as $mb) {
        if ($q !== '') {
            $hay = strtolower($mb['address'] . ' ' . $mb['domain']);
            if (strpos($hay, $q) === false) {
                continue;
            }
        }
        $mailboxes[] = $mb;
        if (count($mailboxes) >= $limit) {
            break;
        }
    }

    return mra_scope_annotate([
        'ok' => true,
        'tool' => 'list_mailboxes',
        'count' => count($mailboxes),
        'mailboxes' => $mailboxes,
        'note' => 'Read-only. Scoped to domains you own or may manage. No passwords or SMTP secrets.',
    ], $actor);
}

return [
    'id' => 'email',
    'name' => 'Email',
    'description' => 'List mailboxes for domains in your site scope (read-only, no secrets).',
    'area' => 'email',
    'free' => true,
    'authz' => 'any',
    'status' => 'active',
    'run' => 'mra_skill_email_run',
    'tools' => [
        [
            'name' => 'list_mailboxes',
            'description' => 'List panel mailboxes visible to the current user (domains they own/manage). Never returns passwords.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Optional filter on address/domain'],
                    'limit' => ['type' => 'integer', 'description' => 'Max rows (default 100, max 500)'],
                ],
            ],
        ],
        [
            'name' => 'email_skill_status',
            'description' => 'Report Email skill status and available tools.',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
