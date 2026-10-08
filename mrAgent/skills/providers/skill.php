<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * @param array<string,mixed> $args
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function mra_skill_providers_run($tool, array $args, array $cfg, $username)
{
    $tool = strtolower(trim((string) $tool));
    if ($tool === 'list_providers') {
        $providers = mra_provider_status($cfg, $username);
        return [
            'ok' => true,
            'tool' => 'list_providers',
            'providers' => $providers,
            'note' => 'Never returns raw provider API key values.',
        ];
    }
    return ['ok' => false, 'error' => 'Unknown providers tool: ' . $tool];
}

return [
    'id' => 'providers',
    'name' => 'Providers',
    'description' => 'List configured chat providers (OpenAI, Anthropic, custom, local, free). Keys stay masked.',
    'area' => 'providers',
    'free' => true,
    'authz' => 'any',
    'status' => 'active',
    'run' => 'mra_skill_providers_run',
    'tools' => [
        [
            'name' => 'list_providers',
            'description' => 'List available chat providers and whether a key is configured (never returns key values).',
            'parameters' => [
                'type' => 'object',
                'properties' => (object) [],
            ],
        ],
    ],
];
