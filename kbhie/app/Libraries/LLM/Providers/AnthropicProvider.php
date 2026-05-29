<?php

namespace App\Libraries\LLM\Providers;

use App\Libraries\LLM\LLMProviderInterface;
use Config\Services;

/**
 * Anthropic Claude — Messages API.
 * Docs: https://docs.anthropic.com/en/api/messages
 */
class AnthropicProvider implements LLMProviderInterface
{
    public function __construct(private string $apiKey, private string $model = 'claude-3-5-haiku-latest') {}

    public function complete(string $prompt, array $opts = []): array
    {
        if (! $this->apiKey) {
            return ['text' => '', 'error' => 'Anthropic API key not configured (set llm.anthropic_key in .env)', 'raw' => []];
        }
        $client = Services::curlrequest(['timeout' => 60]);

        $payload = [
            'model'       => $opts['model'] ?? $this->model,
            'max_tokens'  => (int) ($opts['max_tokens'] ?? 1024),
            'temperature' => (float) ($opts['temperature'] ?? 0.7),
            'messages'    => [['role' => 'user', 'content' => $prompt]],
        ];
        if (! empty($opts['system'])) $payload['system'] = $opts['system'];

        try {
            $r = $client->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);
            $body = json_decode((string) $r->getBody(), true) ?: [];
            $text = $body['content'][0]['text'] ?? '';
            return [
                'text'  => $text,
                'usage' => $body['usage'] ?? [],
                'raw'   => $body,
            ];
        } catch (\Throwable $e) {
            return ['text' => '', 'error' => $e->getMessage(), 'raw' => []];
        }
    }
}
