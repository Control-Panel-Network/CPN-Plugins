<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * OpenAI-compatible chat (OpenAI, custom base URL, optional local).
 *
 * @param array<int,array<string,mixed>> $messages
 * @return array{ok:bool,reply?:string,error?:string,raw?:mixed}
 */
function mra_openai_chat($messages, $model, $apiKey, $baseUrl, $withTools = true, $maxTokens = 1024, $timeout = 60, $allowPrivate = false, $maxBytes = 0)
{
    $baseUrl = rtrim((string) $baseUrl, '/');
    if ($baseUrl === '') {
        $baseUrl = 'https://api.openai.com/v1';
    }
    if ($apiKey === '' && strpos($baseUrl, '127.0.0.1') === false && strpos($baseUrl, 'localhost') === false && strpos($baseUrl, '::1') === false) {
        return ['ok' => false, 'error' => 'Missing provider API key for this OpenAI-compatible endpoint.'];
    }
    $payload = [
        'model' => (string) $model,
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => max(64, min(8192, (int) $maxTokens)),
    ];
    if ($withTools) {
        $payload['tools'] = mra_tools_openai_format();
        $payload['tool_choice'] = 'auto';
    }
    $headers = ['Content-Type: application/json'];
    if ($apiKey !== '') {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }
    $resp = mra_http_json(
        'POST',
        $baseUrl . '/chat/completions',
        $payload,
        $headers,
        max(5, (int) $timeout),
        (bool) $allowPrivate,
        (int) $maxBytes
    );
    if (empty($resp['ok'])) {
        return ['ok' => false, 'error' => $resp['error'] ?? 'OpenAI-compatible request failed', 'raw' => $resp['body'] ?? null];
    }
    return ['ok' => true, 'raw' => $resp['body']];
}

function mra_openai_extract_message(array $body)
{
    return isset($body['choices'][0]['message']) && is_array($body['choices'][0]['message'])
        ? $body['choices'][0]['message']
        : [];
}
