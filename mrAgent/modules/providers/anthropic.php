<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * Anthropic Messages API (non-stream MVP).
 *
 * @param array<int,array<string,string>> $messages OpenAI-style roles
 * @return array{ok:bool,reply?:string,error?:string,raw?:mixed}
 */
function mra_anthropic_chat($messages, $model, $apiKey, $withTools = true, $maxTokens = 1024)
{
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'Missing Anthropic provider API key.'];
    }
    $system = '';
    $converted = [];
    foreach ($messages as $m) {
        $role = (string) ($m['role'] ?? 'user');
        $content = (string) ($m['content'] ?? '');
        if ($role === 'system') {
            $system .= ($system === '' ? '' : "\n") . $content;
            continue;
        }
        if ($role === 'assistant' || $role === 'user') {
            $converted[] = ['role' => $role, 'content' => $content];
        }
    }
    if ($system === '') {
        $system = 'You are Mr Agent, a helpful CPN Panel assistant. Prefer safe read-only guidance. Never request or echo provider API keys.';
    }
    $payload = [
        'model' => (string) $model,
        'max_tokens' => max(64, min(8192, (int) $maxTokens)),
        'system' => $system,
        'messages' => $converted,
    ];
    if ($withTools) {
        $tools = [];
        foreach (mra_tool_definitions() as $t) {
            $tools[] = [
                'name' => $t['name'],
                'description' => $t['description'],
                'input_schema' => $t['parameters'],
            ];
        }
        $payload['tools'] = $tools;
    }
    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ];
    $resp = mra_http_json('POST', 'https://api.anthropic.com/v1/messages', $payload, $headers, 60);
    if (empty($resp['ok'])) {
        return ['ok' => false, 'error' => $resp['error'] ?? 'Anthropic request failed', 'raw' => $resp['body'] ?? null];
    }
    return ['ok' => true, 'raw' => $resp['body']];
}

function mra_anthropic_extract_text(array $body)
{
    $parts = [];
    if (!isset($body['content']) || !is_array($body['content'])) {
        return '';
    }
    foreach ($body['content'] as $block) {
        if (!is_array($block)) {
            continue;
        }
        if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
            $parts[] = (string) $block['text'];
        }
    }
    return trim(implode("\n", $parts));
}

/**
 * @return array<int,array{name:string,id:string,input:array}>
 */
function mra_anthropic_extract_tool_uses(array $body)
{
    $out = [];
    if (!isset($body['content']) || !is_array($body['content'])) {
        return $out;
    }
    foreach ($body['content'] as $block) {
        if (!is_array($block) || ($block['type'] ?? '') !== 'tool_use') {
            continue;
        }
        $out[] = [
            'name' => (string) ($block['name'] ?? ''),
            'id' => (string) ($block['id'] ?? ''),
            'input' => isset($block['input']) && is_array($block['input']) ? $block['input'] : [],
        ];
    }
    return $out;
}
