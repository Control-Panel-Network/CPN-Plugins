<?php
if (!defined('MRA_INIT')) {
    exit;
}

/**
 * OpenAI-compatible chat (OpenAI, custom base URL, optional local).
 *
 * @param array<int,array<string,string>> $messages
 * @param array<string,mixed> $cfg
 * @return array{ok:bool,reply?:string,error?:string,raw?:mixed}
 */
function mra_openai_chat($messages, $model, $apiKey, $baseUrl, $withTools = true)
{
    $baseUrl = rtrim((string) $baseUrl, '/');
    if ($baseUrl === '') {
        $baseUrl = 'https://api.openai.com/v1';
    }
    if ($apiKey === '' && strpos($baseUrl, '127.0.0.1') === false && strpos($baseUrl, 'localhost') === false) {
        return ['ok' => false, 'error' => 'Missing provider API key for this OpenAI-compatible endpoint.'];
    }
    $payload = [
        'model' => (string) $model,
        'messages' => $messages,
        'temperature' => 0.3,
    ];
    if ($withTools) {
        $payload['tools'] = mra_tools_openai_format();
        $payload['tool_choice'] = 'auto';
    }
    $headers = ['Content-Type: application/json'];
    if ($apiKey !== '') {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }
    $resp = mra_http_json('POST', $baseUrl . '/chat/completions', $payload, $headers, 60);
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
