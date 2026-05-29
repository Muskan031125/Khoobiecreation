<?php

namespace App\Libraries\LLM\Providers;

use App\Libraries\LLM\LLMProviderInterface;
use Config\Services;

/**
 * OpenAI Chat Completions — also drives Kimi (Moonshot) by passing base_url=https://api.moonshot.cn/v1
 * and any other OpenAI-compatible provider (Together AI, Groq, OpenRouter, local Ollama).
 */
class OpenAIProvider implements LLMProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-4o-mini',
        private string $baseUrl = 'https://api.openai.com/v1',
    ) {}

    public function complete(string $prompt, array $opts = []): array
    {
        if (! $this->apiKey) {
            return ['text' => '', 'error' => 'API key not configured for ' . $this->baseUrl, 'raw' => []];
        }
        $client = Services::curlrequest(['timeout' => 60]);

        $messages = [];
        if (! empty($opts['system'])) $messages[] = ['role' => 'system', 'content' => $opts['system']];
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model'       => $opts['model'] ?? $this->model,
            'messages'    => $messages,
            'max_tokens'  => (int) ($opts['max_tokens'] ?? 1024),
            'temperature' => (float) ($opts['temperature'] ?? 0.7),
        ];

        try {
            $r = $client->request('POST', rtrim($this->baseUrl, '/') . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);
            $body = json_decode((string) $r->getBody(), true) ?: [];
            return [
                'text'  => $body['choices'][0]['message']['content'] ?? '',
                'usage' => $body['usage'] ?? [],
                'raw'   => $body,
            ];
        } catch (\Throwable $e) {
            return ['text' => '', 'error' => $e->getMessage(), 'raw' => []];
        }
    }
}
