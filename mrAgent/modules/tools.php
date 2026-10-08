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
 * Stopwords so "it"/"is" do not match "websites" / "list".
 *
 * @return array<string,bool>
 */
function mra_search_stopwords()
{
    static $map = null;
    if (is_array($map)) {
        return $map;
    }
    $words = [
        'a', 'an', 'the', 'is', 'it', 'to', 'of', 'in', 'on', 'for', 'and', 'or', 'at', 'by',
        'what', 'where', 'how', 'when', 'who', 'why', 'which', 'do', 'does', 'did', 'can',
        'could', 'would', 'should', 'me', 'my', 'you', 'your', 'we', 'our', 'i', 'am', 'are',
        'be', 'been', 'was', 'were', 'will', 'with', 'from', 'this', 'that', 'these', 'those',
        'hello', 'hi', 'hey', 'please', 'thanks', 'thank', 'today', 'day', 'now', 'tell',
        'about', 'just', 'like', 'also', 'any', 'some', 'there', 'here', 'then', 'than',
        'ok', 'okay', 'yes', 'no', 'not', 'into', 'out', 'up', 'down', 'over', 'under',
    ];
    $map = [];
    foreach ($words as $w) {
        $map[$w] = true;
    }
    return $map;
}

/**
 * @return array<int,array<string,mixed>>
 */
function mra_search_corpus($query, $limit = 8)
{
    $query = strtolower(trim((string) $query));
    $query = preg_replace('/[^a-z0-9\s\-\/]/', ' ', $query);
    $terms = preg_split('/\s+/', (string) $query) ?: [];
    $stop = mra_search_stopwords();
    $terms = array_values(array_filter($terms, function ($t) use ($stop) {
        $t = strtolower(trim((string) $t));
        if (strlen($t) < 3) {
            return false;
        }
        return empty($stop[$t]);
    }));
    if (empty($terms)) {
        return [];
    }
    $scored = [];
    foreach (mra_load_help_corpus() as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = strtolower((string) ($item['title'] ?? ''));
        $path = strtolower((string) ($item['path'] ?? ''));
        $help = strtolower((string) ($item['help'] ?? ''));
        $tags = strtolower(implode(' ', isset($item['tags']) && is_array($item['tags']) ? $item['tags'] : []));
        $hay = $title . ' ' . $path . ' ' . $help . ' ' . $tags;
        $score = 0;
        $matched = 0;
        foreach ($terms as $t) {
            $hitTerm = false;
            if (preg_match('/(^|[^a-z0-9])' . preg_quote($t, '/') . '([^a-z0-9]|$)/', $title)) {
                $score += 5;
                $hitTerm = true;
            } elseif (preg_match('/(^|[^a-z0-9])' . preg_quote($t, '/') . '([^a-z0-9]|$)/', $path)) {
                $score += 4;
                $hitTerm = true;
            } elseif (preg_match('/(^|[^a-z0-9])' . preg_quote($t, '/') . '([^a-z0-9]|$)/', $tags)) {
                $score += 3;
                $hitTerm = true;
            } elseif (preg_match('/(^|[^a-z0-9])' . preg_quote($t, '/') . '([^a-z0-9]|$)/', $help)) {
                $score += 2;
                $hitTerm = true;
            } elseif (strlen($t) >= 5 && strpos($hay, $t) !== false) {
                $score += 1;
                $hitTerm = true;
            }
            if ($hitTerm) {
                $matched++;
            }
        }
        if ($matched > 0 && $score >= 3) {
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
