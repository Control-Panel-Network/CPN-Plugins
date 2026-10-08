<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * MCP tool surface for Mr Agent.
 * Tool definitions and execution come from the skills registry (one skill per CPN area).
 *
 * @return array<int,array<string,mixed>>
 */
function mra_tool_definitions()
{
    $defs = [];
    foreach (mra_skills_tool_definitions() as $t) {
        $defs[] = [
            'name' => $t['name'],
            'description' => $t['description'],
            'parameters' => $t['parameters'],
        ];
    }
    return $defs;
}

function mra_load_help_corpus()
{
    static $items = null;
    if (is_array($items)) {
        return $items;
    }
    $path = MRA_ROOT . '/data/help/cpn-routes.json';
    $raw = is_file($path) ? (string) @file_get_contents($path) : '';
    $data = json_decode($raw, true);
    $items = (is_array($data) && isset($data['items']) && is_array($data['items'])) ? $data['items'] : [];
    return $items;
}

/**
 * @return array<int,array<string,mixed>>
 */
function mra_search_corpus($query, $limit = 8)
{
    $query = strtolower(trim((string) $query));
    $terms = preg_split('/\s+/', $query) ?: [];
    $terms = array_values(array_filter($terms, function ($t) {
        return strlen($t) >= 2;
    }));
    if (empty($terms)) {
        return [];
    }
    $scored = [];
    foreach (mra_load_help_corpus() as $item) {
        if (!is_array($item)) {
            continue;
        }
        $hay = strtolower(
            (string) ($item['title'] ?? '') . ' ' .
            (string) ($item['path'] ?? '') . ' ' .
            (string) ($item['help'] ?? '') . ' ' .
            implode(' ', isset($item['tags']) && is_array($item['tags']) ? $item['tags'] : [])
        );
        $score = 0;
        foreach ($terms as $t) {
            if (strpos($hay, $t) !== false) {
                $score += 2;
            }
            if (isset($item['title']) && stripos((string) $item['title'], $t) !== false) {
                $score += 3;
            }
            if (isset($item['path']) && stripos((string) $item['path'], $t) !== false) {
                $score += 2;
            }
        }
        if ($score > 0) {
            $scored[] = ['score' => $score, 'item' => $item];
        }
    }
    usort($scored, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    $out = [];
    foreach (array_slice($scored, 0, max(1, (int) $limit)) as $row) {
        $it = $row['item'];
        $out[] = [
            'title' => (string) ($it['title'] ?? ''),
            'path' => (string) ($it['path'] ?? ''),
            'help' => (string) ($it['help'] ?? ''),
            'score' => $row['score'],
        ];
    }
    return $out;
}

/**
 * Execute a single allowlisted MCP tool via skills.
 *
 * @param array<string,mixed> $cfg
 * @param array<string,mixed> $args
 * @return array<string,mixed>
 */
function mra_tool_execute($name, array $args, array $cfg, $username)
{
    return mra_skills_execute($name, $args, $cfg, $username);
}

/**
 * OpenAI-style tools array for chat.completions.
 */
function mra_tools_openai_format()
{
    $out = [];
    foreach (mra_tool_definitions() as $t) {
        $out[] = [
            'type' => 'function',
            'function' => [
                'name' => $t['name'],
                'description' => $t['description'],
                'parameters' => $t['parameters'],
            ],
        ];
    }
    return $out;
}
