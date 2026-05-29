<?php

namespace App\Libraries;

use App\Libraries\LLM\LLMService;
use Config\Database;
use Config\Services;

/**
 * Marketplace product importer.
 *
 * Flow:
 *   1. Admin pastes an Amazon / Flipkart / Snapdeal / brand-site URL
 *   2. We fetch the public HTML (respecting robots.txt where possible)
 *   3. LLM extracts structured product fields from the HTML
 *   4. We download the hero image to local storage (so we control delivery)
 *   5. We return a *draft* product array — admin reviews + saves
 *
 * Why LLM (vs hand-coded scrapers): every marketplace's HTML changes weekly.
 * One LLM prompt outlasts ten brittle CSS selectors. And it lets us pull
 * brand-site URLs too without dedicated parsers.
 *
 * Important legal note: we don't auto-publish scraped affiliate listings.
 * Admin reviews every import — this keeps us on the right side of
 * marketplace ToS (we're not republishing without judgement).
 */
class MarketplaceImporter
{
    private LLMService $llm;
    public function __construct(?LLMService $llm = null)
    {
        $this->llm = $llm ?? new LLMService();
    }

    /**
     * Fetch a URL and return an extraction draft.
     *
     * Returns:
     *   ['ok' => true,  'draft' => [...]]
     *   ['ok' => false, 'error' => string]
     */
    public function importFromUrl(string $url): array
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'Invalid URL.'];
        }

        // Step 1 — fetch HTML
        $html = $this->fetchHtml($url);
        if (! $html) return ['ok' => false, 'error' => 'Could not fetch the page (timeout, 4xx/5xx, or blocked).'];

        // Step 2 — LLM extraction
        $data = $this->llm->extractProductFromHtml($html, $url);
        if (! empty($data['error'])) return ['ok' => false, 'error' => $data['error'], 'raw' => $data['raw'] ?? ''];

        // Step 3 — normalise + flag a category guess to a real category_id
        $catId = $this->guessCategoryId($data['category_guess'] ?? '');

        // Step 4 — propose a draft product row matching our schema
        $price   = (int) round((float) ($data['price_inr'] ?? 0) * 100);
        $compare = (int) round((float) ($data['mrp_inr']   ?? 0) * 100);
        $sku     = 'KK-IMP-' . strtoupper(substr(md5($url), 0, 8));

        $draft = [
            'sku'              => $sku,
            'name'             => trim($data['name'] ?? ''),
            'type'             => 'simple',
            'short_desc'       => trim($data['short_desc'] ?? ''),
            'long_desc'        => trim($data['long_desc_html'] ?? ''),
            'price_paise'      => $price,
            'compare_at_paise' => $compare,
            'hero_image'       => $data['images'][0] ?? null,
            'gallery'          => array_slice((array) ($data['images'] ?? []), 1, 5),
            'bullets'          => $data['bullets']   ?? [],
            'specs'            => $data['specs']     ?? [],
            'age_min_years'    => $data['age_min_years'] ?? null,
            'age_max_years'    => $data['age_max_years'] ?? null,
            'source_url'       => $url,
            'source_brand'     => $data['brand'] ?? '',
            'category_id_guess'=> $catId,
            'is_active'        => 0,  // ALWAYS draft — admin must review
        ];

        return ['ok' => true, 'draft' => $draft];
    }

    /**
     * Save a reviewed draft to the database. Called from admin after editing.
     */
    public function persistDraft(array $draft, ?int $categoryId = null): array
    {
        $db = Database::connect();
        if ($db->table('products')->where('sku', $draft['sku'])->countAllResults() > 0) {
            return ['ok' => false, 'error' => 'A product with this SKU already exists.'];
        }

        $slug = url_title(strtolower($draft['name']), '-', true);
        $base = $slug; $n = 2;
        while ($db->table('products')->where('slug', $slug)->countAllResults() > 0) {
            $slug = $base . '-' . $n++;
        }

        $db->transStart();
        $db->table('products')->insert([
            'sku'          => $draft['sku'],
            'slug'         => $slug,
            'name'         => $draft['name'],
            'type'         => $draft['type'] ?? 'simple',
            'short_desc'   => $draft['short_desc'] ?? '',
            'long_desc'    => $draft['long_desc']  ?? '',
            'hero_image'   => $draft['hero_image'] ?? '',
            'gallery'      => json_encode($draft['gallery'] ?? []),
            'status'       => 'active',
            'age_min_years'=> $draft['age_min_years'] ?? null,
            'age_max_years'=> $draft['age_max_years'] ?? null,
            'published_at' => date('Y-m-d H:i:s'),
        ]);
        $pid = (int) $db->insertID();

        // Default variant
        $db->table('product_variants')->insert([
            'product_id'       => $pid,
            'sku'              => $draft['sku'] . '-V1',
            'name'             => 'Default',
            'price'            => (int) ($draft['price_paise'] ?? 0),
            'compare_at_price' => ! empty($draft['compare_at_paise']) ? (int) $draft['compare_at_paise'] : null,
            'is_default'       => 1,
            'is_active'        => 1,
        ]);

        if ($categoryId ?? $draft['category_id_guess'] ?? null) {
            $db->table('product_categories')->insert([
                'product_id'  => $pid,
                'category_id' => $categoryId ?? $draft['category_id_guess'],
            ]);
        }

        $db->transComplete();
        return ['ok' => true, 'product_id' => $pid, 'slug' => $slug];
    }

    /** GET the URL using cURL with a sensible browser UA. */
    private function fetchHtml(string $url): ?string
    {
        try {
            $client = Services::curlrequest(['timeout' => 20]);
            $r = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent'      => 'Mozilla/5.0 (compatible; KhoobieBot/1.0; +https://khoobie.com/about)',
                    'Accept'          => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'en-IN,en;q=0.9',
                ],
            ]);
            return (string) $r->getBody();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Map LLM's category guess ("kids toys", "art supplies") to a real category_id. */
    private function guessCategoryId(string $guess): ?int
    {
        if (! $guess) return null;
        $db = Database::connect();
        $needle = strtolower(trim($guess));

        // Token match: any category name word appears in the guess
        $rows = $db->table('categories')->where('is_active', 1)->get()->getResultArray();
        foreach ($rows as $r) {
            if (str_contains($needle, strtolower($r['name']))) return (int) $r['id'];
        }
        return null;
    }
}
