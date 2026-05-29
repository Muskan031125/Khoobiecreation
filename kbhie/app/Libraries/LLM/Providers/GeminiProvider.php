<?php

namespace App\Libraries\LLM\Providers;

use App\Libraries\LLM\LLMProviderInterface;
use Config\Services;

/**
 * Google Gemini — generateContent API.
 * Docs: https://ai.google.dev/api/generate-content
 */
class GeminiProvider implements LLMProviderInterface
{
    public function __construct(private string $apiKey, private string $model = 'gemini-1.5-flash') {}

    public function complete(string $prompt, array $opts = []): array
    {
        if (! $this->apiKey) {
            return ['text' => '', 'error' => 'Gemini API key not configured (set llm.gemini_key in .env)', 'raw' => []];
        }
        $client = Services::curlrequest(['timeout' => 60]);
        $model  = $opts['model'] ?? $this->model;

        $contents = [];
        if (! empty($opts['system'])) {
            // Gemini system instructions go in a separate field
            $sysField = ['parts' => [['text' => $opts['system']]]];
        }
        $contents[] = ['parts' => [['text' => $prompt]]];

        $payload = [
            'contents'         => $contents,
            'generationConfig' => [
                'maxOutputTokens' => (int) ($opts['max_tokens'] ?? 1024),
                'temperature'     => (float) ($opts['temperature'] ?? 0.7),
            ],
        ];
        if (isset($sysField)) $payload['systemInstruction'] = $sysField;

        try {
            $r = $client->request('POST', "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}", [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => json_encode($payload),
            ]);
            $body = json_decode((string) $r->getBody(), true) ?: [];
            $text = '';
            foreach ($body['candidates'][0]['content']['parts'] ?? [] as $part) {
                if (isset($part['text'])) $text .= $part['text'];
            }
            return [
                'text'  => $text,
                'usage' => $body['usageMetadata'] ?? [],
                'raw'   => $body,
            ];
        } catch (\Throwable $e) {
            return ['text' => '', 'error' => $e->getMessage(), 'raw' => []];
        }
    }
}
