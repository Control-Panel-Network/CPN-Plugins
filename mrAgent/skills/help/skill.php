<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Help / Menu skill (free path). Uses bundled CPN route corpus.
 *
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_help_run($tool, array $args, array $cfg, $username)
{
    $tool = strtolower(trim((string) $tool));
    if ($tool === 'search_menu' || $tool === 'search_docs') {
        $q = isset($args['query']) ? (string) $args['query'] : '';
        $hits = mra_search_corpus($q, 8);
        return [
            'ok' => true,
            'tool' => $tool,
            'query' => $q,
            'results' => $hits,
            'note' => 'Free lightweight skill: no provider API key required.',
        ];
    }
    return ['ok' => false, 'error' => 'Unknown help tool: ' . $tool];
}

return [
    'id' => 'help',
    'name' => 'Help and Menu',
    'description' => 'Search CPN sidebar routes and bundled help hints. Works on the free lightweight path.',
    'area' => 'help',
    'free' => true,
    'authz' => 'any',
    'status' => 'active',
    'run' => 'mra_skill_help_run',
    'tools' => [
        [
            'name' => 'search_menu',
            'description' => 'Search CPN Panel sidebar and common routes by keyword. Use for "where is X" questions.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Search words'],
                ],
                'required' => ['query'],
            ],
        ],
        [
            'name' => 'search_docs',
            'description' => 'Search bundled CPN help hints for a topic (routes, settings, plugins).',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Topic or feature name'],
                ],
                'required' => ['query'],
            ],
        ],
    ],
];
