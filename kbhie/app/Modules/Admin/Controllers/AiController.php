<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\LLM\LLMService;
use Config\Database;

/**
 * Admin AI endpoints — JSON microservices called from the product edit form.
 * Each maps to one LLMService helper.
 */
class AiController extends BaseAdminController
{
    public function description()
    {
        $product = $this->loadProductFromPost();
        if (! $product) return $this->fail('Product not found');

        $words = max(40, min(200, (int) $this->request->getPost('words') ?: 80));
        $text  = (new LLMService())->generateProductDescription($product, $words);
        return $this->response->setJSON(['ok' => (bool) $text, 'text' => $text]);
    }

    public function seoMeta()
    {
        $product = $this->loadProductFromPost();
        if (! $product) return $this->fail('Product not found');

        $res = (new LLMService())->generateSeoMeta($product);
        return $this->response->setJSON(['ok' => true, 'title' => $res['title'] ?? '', 'description' => $res['description'] ?? '']);
    }

    public function altText()
    {
        $product = $this->loadProductFromPost();
        if (! $product) return $this->fail('Product not found');

        // Text-only fallback: build alt from product attributes (vision LLM would be next)
        $prompt = "Write a 100-character alt text for the hero image of this product, for image SEO + accessibility. Product name: {$product['name']}. Brief: {$product['short_desc']}. Return only the alt text, no quotes.";
        $res = (new LLMService())->complete($prompt, ['max_tokens' => 80, 'temperature' => 0.5]);
        $alt = trim((string) ($res['text'] ?? ''), " \"'\n");
        return $this->response->setJSON(['ok' => (bool) $alt, 'alt' => $alt]);
    }

    public function reviewSummary()
    {
        $productId = (int) $this->request->getPost('product_id');
        if (! $productId) return $this->fail('product_id required');

        $reviews = Database::connect()->table('reviews')
            ->select('rating, title, body')
            ->where('product_id', $productId)
            ->where('status', 'published')
            ->limit(50)->get()->getResultArray();

        if (empty($reviews)) return $this->response->setJSON(['ok' => false, 'error' => 'No published reviews yet.']);

        $summary = (new LLMService())->summarizeReviews($reviews);
        return $this->response->setJSON(['ok' => true, 'summary' => $summary, 'review_count' => count($reviews)]);
    }

    public function blogDraft()
    {
        $topic   = trim((string) $this->request->getPost('topic'));
        $keywords= trim((string) $this->request->getPost('keywords'));
        $words   = max(300, min(2000, (int) $this->request->getPost('words') ?: 800));
        if (! $topic) return $this->fail('topic required');

        $system = 'You are a parenting blog writer at Khoobie, a screen-free kids learning brand in India. Tone: warm, practical, India-rooted, expertise-led. Use Indian English. No emojis. Use H2 and H3 headings (Markdown ##/###). Include 2-3 internal-link suggestions like [DIY Paint Kits](/shop/diy-paint-kits) where relevant. Write for parents, not search engines — but naturally include the target keywords.';
        $prompt = "Write a {$words}-word blog post.\nTopic: {$topic}\nTarget keywords (use naturally): {$keywords}\n\nStructure:\n- Hook opening (2 sentences)\n- 4-6 H2 sections with practical advice\n- Concrete examples (real Indian context — Mumbai, Bangalore, Delhi parents)\n- Closing CTA paragraph linking to relevant Khoobie products\n\nReturn ONLY Markdown. No commentary, no title line — just body.";

        $res = (new LLMService())->complete($prompt, ['max_tokens' => 4000, 'temperature' => 0.7, 'system' => $system]);
        $text = (string) ($res['text'] ?? '');

        // Also generate a title + meta description
        $metaRes = (new LLMService())->complete(
            "Write a blog post title (under 60 chars) and meta description (under 160 chars) for a post about: {$topic}. Return JSON: {\"title\":\"...\",\"meta\":\"...\"}",
            ['max_tokens' => 200, 'temperature' => 0.5]
        );
        $meta = [];
        if (preg_match('/\{.*\}/s', (string) $metaRes['text'], $m)) {
            $meta = json_decode($m[0], true) ?: [];
        }

        return $this->response->setJSON([
            'ok'        => (bool) $text,
            'body_md'   => $text,
            'title'     => $meta['title'] ?? $topic,
            'meta_desc' => $meta['meta']  ?? '',
        ]);
    }

    private function loadProductFromPost(): ?array
    {
        $id = (int) $this->request->getPost('product_id');
        if ($id) return Database::connect()->table('products')->where('id', $id)->get()->getRowArray() ?: null;

        // Allow live-edit (form not saved yet) — accept name + short_desc + type
        $name = trim((string) $this->request->getPost('name'));
        if (! $name) return null;
        return [
            'id'            => 0,
            'name'          => $name,
            'short_desc'    => $this->request->getPost('short_desc'),
            'type'          => $this->request->getPost('type') ?: 'simple',
            'age_min_years' => $this->request->getPost('age_min_years'),
            'age_max_years' => $this->request->getPost('age_max_years'),
        ];
    }

    private function fail(string $err)
    {
        return $this->response->setJSON(['ok' => false, 'error' => $err]);
    }
}
