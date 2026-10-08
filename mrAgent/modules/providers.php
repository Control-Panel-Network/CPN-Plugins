<?php
if (!defined('MRA_INIT')) {
    exit;
}

require_once __DIR__ . '/providers/openai_compat.php';
require_once __DIR__ . '/providers/anthropic.php';

/**
 * @return array<int,array<string,mixed>>
 */
function mra_provider_catalog()
{
    return [
        ['id' => 'free', 'label' => 'Free lightweight (CPN help)', 'models' => ['cpn-help'], 'needs_key' => false],
        ['id' => 'openai', 'label' => 'OpenAI', 'models' => ['gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini'], 'needs_key' => true, 'base' => 'https://api.openai.com/v1'],
        ['id' => 'anthropic', 'label' => 'Anthropic Claude', 'models' => ['claude-3-5-haiku-latest', 'claude-3-5-sonnet-latest', 'claude-sonnet-4-0'], 'needs_key' => true],
        ['id' => 'custom', 'label' => 'Custom OpenAI-compatible', 'models' => ['default'], 'needs_key' => true],
        ['id' => 'local', 'label' => 'Local OpenAI-compatible (loopback)', 'models' => ['llama3.2:1b', 'qwen2.5:3b'], 'needs_key' => false],
    ];
}

/**
 * Status without exposing key material.
 *
 * @return array<int,array<string,mixed>>
 */
function mra_provider_status(array $cfg, $username)
{
    $out = [];
    foreach (mra_provider_catalog() as $p) {
        $id = $p['id'];
        $configured = true;
        if (!empty($p['needs_key'])) {
            $key = mra_resolve_api_key($id, $username, $cfg);
            $configured = $key !== '';
            if ($id === 'custom' && empty($cfg['custom_base_url'])) {
                $store = mra_keys_store($cfg['domain'] ?? null);
                $configured = $configured && (!empty($store['host']['custom_base_url']) || !empty($cfg['custom_base_url']));
            }
        }
        $out[] = [
            'id' => $id,
            'label' => $p['label'],
            'models' => $p['models'],
            'configured' => $configured,
            'needs_key' => !empty($p['needs_key']),
        ];
    }
    return $out;
}

function mra_default_model_for($provider)
{
    foreach (mra_provider_catalog() as $p) {
        if ($p['id'] === $provider && !empty($p['models'][0])) {
            return (string) $p['models'][0];
        }
    }
    return 'cpn-help';
}
