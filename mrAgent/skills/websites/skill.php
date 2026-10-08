<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Read-only websites list scoped to the authenticated CPN user.
 *
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_websites_run($tool, array $args, array $cfg, $username)
{
    $tool = strtolower(trim((string) $tool));
    if ($tool !== 'list_websites') {
        return ['ok' => false, 'error' => 'Unknown websites tool: ' . $tool];
    }

    $actor = mra_scope_actor();
    if ($actor['username'] === '') {
        return ['ok' => false, 'error' => 'Sign in required.'];
    }

    $q = isset($args['query']) ? strtolower(trim((string) $args['query'])) : '';
    $limit = isset($args['limit']) ? (int) $args['limit'] : 50;
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 200) {
        $limit = 200;
    }

    $websites = [];
    foreach (mra_scope_list_websites($actor) as $row) {
        // Never expose another tenant home path even if registry is wrong.
        $doc = (string) ($row['docroot'] ?? '');
        if ($doc !== '' && !mra_scope_path_allowed($doc, $actor)) {
            $row['docroot'] = '';
            $row['docroot_hidden'] = true;
        }
        if ($q !== '') {
            $hay = strtolower($row['domain'] . ' ' . $row['owner'] . ' ' . $row['php']);
            if (strpos($hay, $q) === false) {
                continue;
            }
        }
        $websites[] = $row;
        if (count($websites) >= $limit) {
            break;
        }
    }

    return mra_scope_annotate([
        'ok' => true,
        'tool' => 'list_websites',
        'count' => count($websites),
        'websites' => $websites,
        'note' => 'Read-only. Results are limited to sites you own or may manage (panel site ACL). Admins see all registered sites.',
    ], $actor);
}

return [
    'id' => 'websites',
    'name' => 'Websites',
    'description' => 'Read-only website registry scoped to the signed-in CPN user (owner/grant). Admins see all.',
    'area' => 'websites',
    'free' => true,
    'authz' => 'any',
    'status' => 'active',
    'run' => 'mra_skill_websites_run',
    'tools' => [
        [
            'name' => 'list_websites',
            'description' => 'List CPN websites the current user may manage (read-only). Optional query filters by domain/owner.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Optional filter text'],
                    'limit' => ['type' => 'integer', 'description' => 'Max rows (default 50, max 200)'],
                ],
            ],
        ],
    ],
];
