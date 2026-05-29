<?php

namespace App\Libraries\LLM;

use App\Libraries\LLM\Providers\AnthropicProvider;
use App\Libraries\LLM\Providers\GeminiProvider;
use App\Libraries\LLM\Providers\OpenAIProvider;

/**
 * Provider-agnostic LLM gateway. Reads provider + key + model from .env or settings
 * and routes a unified `complete($prompt, $opts)` call to the right backend.
 *
 * Supported providers (set llm.provider in .env):
 *   anthropic  → Claude (https://docs.anthropic.com)
 *   openai     → GPT-4o/4o-mini
 *   gemini     → Google Gemini 1.5 / 2.0
 *   kimi       → Moonshot Kimi (OpenAI-compatible — uses OpenAIProvider with base_url)
 *
 * Switching providers is one env var change. Each provider has the same surface:
 *   $svc = (new LLMService())->using('anthropic');
 *   $text = $svc->complete('Write 80 words on...', ['max_tokens'=>500]);
 *
 * Higher-level helpers (generateProductDescription, generateSeoMeta, summarizeReviews)
 * wrap complete() with curated prompts so callers don't need prompt-engineering knowledge.
 */
class LLMService
{
    private string $provider;
    private ?LLMProviderInterface $instance = null;

    public function __construct(?string $providerOverride = null)
    {
        $this->provider = $providerOverride ?? env('llm.provider', 'anthropic');
    }

    public function using(string $provider): self
    {
        $clone = clone $this;
        $clone->provider = $provider;
        $clone->instance = null;
        return $clone;
    }

    /** Send a prompt, get text back. Opts: max_tokens, temperature, system, model. */
    public function complete(string $prompt, array $opts = []): array
    {
        return $this->provider()->complete($prompt, $opts);
    }

    /** Generate a 60-120 word product short description (SEO + parent-voice). */
    public function generateProductDescription(array $product, int $targetWords = 80): string
    {
        $name  = $product['name']  ?? 'product';
        $type  = $product['type']  ?? 'simple';
        $age   = isset($product['age_min_years']) ? "Ages {$product['age_min_years']}-{$product['age_max_years']}" : '';
        $attrs = isset($product['attributes']) ? json_encode($product['attributes']) : '';

        $system = "You are a senior product copywriter at Khoobie, a screen-free kids learning brand in India. Voice: warm, India-rooted, parent-trustworthy, concrete. Never use marketing fluff like 'amazing'. Use Indian English. No emojis.";
        $prompt = "Write a {$targetWords}-word product short description for parents browsing on mobile. Product: {$name}. Type: {$type}. {$age}. Key attributes: {$attrs}.

The description should:
- Open with the concrete benefit to the child (skill / fun / outcome)
- Mention what's inside or what they'll do
- Be skimmable, no jargon, no clichés
- Avoid the words 'amazing', 'unique', 'wonderful', 'best'";

        $res = $this->complete($prompt, ['max_tokens' => 350, 'temperature' => 0.7, 'system' => $system]);
        return trim((string) ($res['text'] ?? ''));
    }

    /** Generate SEO meta title (≤60 chars) and meta description (≤160 chars). */
    public function generateSeoMeta(array $product): array
    {
        $name = $product['name'] ?? '';
        $sd   = $product['short_desc'] ?? '';
        $age  = isset($product['age_min_years']) ? "ages {$product['age_min_years']}-{$product['age_max_years']}" : '';

        $system = 'You are an SEO specialist. You write meta tags optimised for click-through. Return strict JSON: {"title": "...", "description": "..."}. Title ≤60 chars. Description ≤160 chars. Include the product name + one searchable keyword + a benefit. No emojis.';
        $prompt = "Product: {$name}. {$age}. Description: {$sd}. Brand: Krafty Khoobie. Return JSON only.";

        $res = $this->complete($prompt, ['max_tokens' => 200, 'temperature' => 0.4, 'system' => $system]);
        $text = (string) ($res['text'] ?? '');
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $j = json_decode($m[0], true);
            if (is_array($j) && isset($j['title'], $j['description'])) return $j;
        }
        return ['title' => '', 'description' => ''];
    }

    /** Summarise a stack of reviews into 3 sentences a parent can skim. */
    public function summarizeReviews(array $reviews): string
    {
        if (empty($reviews)) return '';
        $stack = array_slice(array_map(fn ($r) => '★' . ($r['rating'] ?? '') . ' — ' . trim((string)($r['body'] ?? '')), $reviews), 0, 30);
        $joined = implode("\n", $stack);

        $system = 'You summarise customer reviews of children\'s products. Output 3 sentences in this order: (1) what parents loved, (2) one common concern if any, (3) who this is best for. Neutral tone. No marketing language.';
        $res = $this->complete("Reviews:\n{$joined}", ['max_tokens' => 250, 'temperature' => 0.5, 'system' => $system]);
        return trim((string) ($res['text'] ?? ''));
    }

    /** Extract structured product data from a chunk of HTML / Markdown (marketplace import). */
    public function extractProductFromHtml(string $html, string $sourceUrl = ''): array
    {
        $html = mb_substr(strip_tags($html, '<h1><h2><h3><p><ul><li><img><span>'), 0, 18000);
        $system = 'You are a product data extractor. Given marketplace HTML, return ONLY a JSON object with these keys: name (string), brand (string|null), price_inr (number|null), mrp_inr (number|null), short_desc (string ≤ 200 chars), long_desc_html (string), bullets (array of strings), images (array of full image URLs), specs (object of key→value), age_min_years (int|null), age_max_years (int|null), category_guess (string). No commentary. JSON only.';
        $prompt = "Source URL: {$sourceUrl}\n\nHTML/Text:\n{$html}";

        $res  = $this->complete($prompt, ['max_tokens' => 1500, 'temperature' => 0.2, 'system' => $system]);
        $text = (string) ($res['text'] ?? '');
        if (preg_match('/\{.*\}/s', $text, $m)) {
            return json_decode($m[0], true) ?: ['error' => 'Could not parse LLM JSON', 'raw' => $text];
        }
        return ['error' => 'LLM returned no JSON', 'raw' => $text];
    }

    /** Lazy provider instance + factory. */
    private function provider(): LLMProviderInterface
    {
        if ($this->instance) return $this->instance;
        $this->instance = match ($this->provider) {
            'anthropic' => new AnthropicProvider(env('llm.anthropic_key', ''), env('llm.anthropic_model', 'claude-3-5-haiku-latest')),
            'openai'    => new OpenAIProvider(env('llm.openai_key', ''),    env('llm.openai_model', 'gpt-4o-mini')),
            'kimi'      => new OpenAIProvider(env('llm.kimi_key', ''),      env('llm.kimi_model', 'moonshot-v1-8k'), 'https://api.moonshot.cn/v1'),
            'gemini'    => new GeminiProvider(env('llm.gemini_key', ''),    env('llm.gemini_model', 'gemini-1.5-flash')),
            default     => throw new \RuntimeException("Unknown LLM provider: {$this->provider}"),
        };
        return $this->instance;
    }
}
