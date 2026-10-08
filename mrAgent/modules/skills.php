<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Skill registry: one skill per CPN area.
 * Skills expose MCP tools; Mr Agent chat loads them through mra_tool_* helpers.
 *
 * @return array<string,array<string,mixed>>
 */
function mra_skills_all()
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    $dir = MRA_ROOT . '/skills';
    if (!is_dir($dir)) {
        return $cache;
    }
    $files = glob($dir . '/*/skill.php');
    if (!is_array($files)) {
        return $cache;
    }
    sort($files);
    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }
        $skill = include $file;
        if (!is_array($skill) || empty($skill['id']) || !is_string($skill['id'])) {
            mra_log('skill_skip_invalid', ['file' => basename(dirname($file))]);
            continue;
        }
        $id = strtolower(trim($skill['id']));
        if ($id === '' || isset($cache[$id])) {
            continue;
        }
        $skill['id'] = $id;
        if (!isset($skill['tools']) || !is_array($skill['tools'])) {
            $skill['tools'] = [];
        }
        if (!isset($skill['enabled'])) {
            $skill['enabled'] = true;
        }
        $cache[$id] = $skill;
    }
    return $cache;
}

/**
 * Public skill catalog (no handlers).
 *
 * @return array<int,array<string,mixed>>
 */
function mra_skills_catalog()
{
    $out = [];
    foreach (mra_skills_all() as $skill) {
        if (empty($skill['enabled'])) {
            continue;
        }
        $tools = [];
        foreach ($skill['tools'] as $t) {
            if (!is_array($t) || empty($t['name'])) {
                continue;
            }
            $tools[] = [
                'name' => (string) $t['name'],
                'description' => (string) ($t['description'] ?? ''),
            ];
        }
        $out[] = [
            'id' => (string) $skill['id'],
            'name' => (string) ($skill['name'] ?? $skill['id']),
            'description' => (string) ($skill['description'] ?? ''),
            'area' => (string) ($skill['area'] ?? $skill['id']),
            'free' => !empty($skill['free']),
            'authz' => (string) ($skill['authz'] ?? 'any'),
            'status' => (string) ($skill['status'] ?? 'active'),
            'tools' => $tools,
        ];
    }
    return $out;
}

/**
 * Flatten MCP tool definitions from enabled skills (+ builtin list_skills).
 *
 * @return array<int,array<string,mixed>>
 */
function mra_skills_tool_definitions()
{
    $out = [
        [
            'name' => 'list_skills',
            'description' => 'List Mr Agent skills (CPN areas) and their MCP tools. Use to discover panel capabilities.',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ];
    foreach (mra_skills_all() as $skill) {
        if (empty($skill['enabled'])) {
            continue;
        }
        foreach ($skill['tools'] as $t) {
            if (!is_array($t) || empty($t['name'])) {
                continue;
            }
            $out[] = [
                'name' => (string) $t['name'],
                'description' => (string) ($t['description'] ?? ''),
                'parameters' => isset($t['parameters']) ? $t['parameters'] : [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
                '_skill' => (string) $skill['id'],
            ];
        }
    }
    return $out;
}

/**
 * @param array<string,mixed> $cfg
 * @param array<string,mixed> $args
 * @return array<string,mixed>
 */
function mra_skills_execute($name, array $args, array $cfg, $username)
{
    $name = strtolower(trim((string) $name));
    // Always bind tools to the authenticated session user (ignore spoofed args).
    $sessionUser = strtolower(trim((string) mra_user()));
    if ($sessionUser === '') {
        return ['ok' => false, 'error' => 'Sign in required.'];
    }
    $username = $sessionUser;

    if ($name === 'list_skills') {
        return mra_scope_annotate([
            'ok' => true,
            'tool' => 'list_skills',
            'skills' => mra_skills_catalog(),
        ]);
    }

    foreach (mra_skills_all() as $skill) {
        if (empty($skill['enabled'])) {
            continue;
        }
        $toolNames = [];
        foreach ($skill['tools'] as $t) {
            if (is_array($t) && !empty($t['name'])) {
                $toolNames[] = strtolower((string) $t['name']);
            }
        }
        if (!in_array($name, $toolNames, true)) {
            continue;
        }
        $authz = strtolower((string) ($skill['authz'] ?? 'any'));
        if ($authz === 'owner' && !mra_is_owner()) {
            return [
                'ok' => false,
                'error' => 'This skill requires an owner/admin Mr Agent role.',
                'skill' => $skill['id'],
                'tool' => $name,
            ];
        }
        $run = isset($skill['run']) ? (string) $skill['run'] : '';
        if ($run === '' || !function_exists($run)) {
            return [
                'ok' => false,
                'error' => 'Skill handler missing: ' . $skill['id'],
                'skill' => $skill['id'],
                'tool' => $name,
            ];
        }
        // Strip any client-supplied identity overrides from tool args.
        unset($args['username'], $args['userid'], $args['user_id'], $args['role'], $args['as_user']);
        $result = call_user_func($run, $name, $args, $cfg, $username);
        if (!is_array($result)) {
            return ['ok' => false, 'error' => 'Skill returned invalid result', 'skill' => $skill['id']];
        }
        if (!isset($result['skill'])) {
            $result['skill'] = $skill['id'];
        }
        if (!isset($result['tool'])) {
            $result['tool'] = $name;
        }
        return $result;
    }

    return ['ok' => false, 'error' => 'Tool not allowed: ' . $name];
}

// mra_cpn_data_dir() lives in modules/scope.php (loaded before skills).
