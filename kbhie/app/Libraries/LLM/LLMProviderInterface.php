<?php

namespace App\Libraries\LLM;

/**
 * Common contract every LLM provider implements.
 * Returns ['text' => string, 'usage' => [...], 'raw' => array] on success
 * or ['text' => '', 'error' => string, 'raw' => array] on failure.
 */
interface LLMProviderInterface
{
    public function complete(string $prompt, array $opts = []): array;
}
