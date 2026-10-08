<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Read-only websites list from panel site registry JSON.
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

    $dir = mra_cpn_data_dir() . '/sites';
    if (!is_dir($dir)) {
        return [
            'ok' => true,
            'tool' => 'list_websites',
            'websites' => [],
            'note' => 'No site registry directory found at ' . $dir . '. Panel host data may live elsewhere.',
        ];
    }

    $q = isset($args['query']) ? strtolower(trim((string) $args['query'])) : '';
    $limit = isset($args['limit']) ? (int) $args['limit'] : 50;
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 200) {
        $limit = 200;
    }

    $files = glob($dir . '/*.json');
    if (!is_array($files)) {
        $files = [];
    }
    sort($files);

    $websites = [];
    foreach ($files as $file) {
        if (!is_file($file) || !is_readable($file)) {
            continue;
        }
        $raw = (string) @file_get_contents($file);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            continue;
        }
        $domain = (string) ($data['domain'] ?? $data['name'] ?? pathinfo($file, PATHINFO_FILENAME));
        if ($domain === '' || $domain === '.' || $domain === '..') {
            continue;
        }
        // Skip incomplete stubs (no domain-like key and empty owner).
        if ($domain === 'filegator-lab.local') {
            continue;
        }
        $row = [
            'domain' => $domain,
            'php' => isset($data['php_version']) ? (string) $data['php_version'] : (isset($data['php']) ? (string) $data['php'] : ''),
            'owner' => isset($data['owner']) ? (string) $data['owner'] : (isset($data['username']) ? (string) $data['username'] : ''),
            'docroot' => isset($data['docroot']) ? (string) $data['docroot'] : (isset($data['document_root']) ? (string) $data['document_root'] : ''),
        ];
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

    return [
        'ok' => true,
        'tool' => 'list_websites',
        'count' => count($websites),
        'websites' => $websites,
        'note' => 'Read-only MCP skill. No create/delete. Owner/admin Mr Agent role required.',
    ];
}

return [
    'id' => 'websites',
    'name' => 'Websites',
    'description' => 'Panel-wide read-only website registry (list domains from CPN site JSON).',
    'area' => 'websites',
    'free' => true,
    'authz' => 'owner',
    'status' => 'active',
    'run' => 'mra_skill_websites_run',
    'tools' => [
        [
            'name' => 'list_websites',
            'description' => 'List CPN websites from the host site registry (read-only). Optional query filters by domain/owner.',
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
