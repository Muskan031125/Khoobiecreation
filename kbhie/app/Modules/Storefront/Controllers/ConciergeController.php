<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\LLM\LLMService;
use Config\Database;

/**
 * Khoobie AI Concierge — LLM-powered product recommender + chatbot.
 *
 * Flow:
 *   1. Visitor opens the widget bottom-right, types "I need a craft kit for my 7-year-old who likes animals".
 *   2. We retrieve top 30 candidate products (loose keyword + age match) — RAG context.
 *   3. LLM ranks them, picks 3 with one-line rationales tied to the user's prompt.
 *   4. Frontend renders 3 product cards with deep links.
 *
 * Result: feels like talking to a Khoobie expert; conversion-grade and
 * investor-demo gold ("our website talks to your child's needs").
 */
class ConciergeController extends BaseStoreController
{
    public function ask()
    {
        $q = trim((string) $this->request->getPost('q'));
        if (! $q) return $this->response->setJSON(['ok' => false, 'error' => 'Tell me what you\'re looking for!']);

        $db = Database::connect();

        // Cheap candidate retrieval: LIKE search on name + short_desc with optional age band match
        $candidates = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.short_desc, p.type, p.age_min_years, p.age_max_years, p.rating_avg, p.hero_image,
                      v.price, v.compare_at_price", false)
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->where('p.status', 'active')
            ->groupStart()
                ->like('p.name', $q)
                ->orLike('p.short_desc', $q)
                ->orLike('p.long_desc', $q)
            ->groupEnd()
            ->orderBy('p.sales_count', 'DESC')->orderBy('p.rating_avg', 'DESC')
            ->limit(30)->get()->getResultArray();

        // If LIKE returned nothing, fall back to top bestsellers (still useful)
        if (empty($candidates)) {
            $candidates = $db->table('products p')
                ->select("p.id, p.slug, p.name, p.short_desc, p.type, p.age_min_years, p.age_max_years, p.rating_avg, p.hero_image,
                          v.price, v.compare_at_price", false)
                ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
                ->where('p.status', 'active')
                ->orderBy('p.sales_count', 'DESC')->limit(30)->get()->getResultArray();
        }

        // Compact catalog for the LLM prompt
        $compact = array_map(fn ($p) => [
            'id'       => (int) $p['id'],
            'name'     => $p['name'],
            'type'     => $p['type'],
            'ages'     => $p['age_min_years'] . '-' . $p['age_max_years'],
            'price'    => $p['price'] ? '₹' . round($p['price']/100) : '—',
            'rating'   => (float) $p['rating_avg'],
            'about'    => mb_substr(trim((string)$p['short_desc']), 0, 140),
        ], $candidates);

        $loc = \App\Libraries\LocationService::current();
        $cityContext = ($loc && ! empty($loc['city'])) ? "\n\nThe parent is in {$loc['city']}" . (! empty($loc['locality']) ? ", specifically " . $loc['locality'] : '') . ". If you recommend an in-person item, strongly prefer one in their city. If their city has no in-person option for this need, mention that briefly and recommend online alternatives." : "";

        $system = 'You are the Khoobie AI Concierge — a warm, expert assistant helping Indian parents find the right screen-free product, class, or experience for their kid. You ONLY recommend from the provided catalog (never invent products). Return STRICT JSON: {"reply": "1-2 sentence friendly response addressing the parent\'s ask", "picks": [{"id": int, "why": "one sentence on why this matches"}], "follow_up": "one optional helpful follow-up question"}. Pick at most 3 products. If nothing fits, return picks=[] and use the reply to ask a clarifying question.';
        $prompt = "Parent asked: \"{$q}\"{$cityContext}\n\nCatalog (pick from these IDs only):\n" . json_encode($compact);

        $llm = (new LLMService())->complete($prompt, ['max_tokens' => 800, 'temperature' => 0.5, 'system' => $system]);
        $raw = (string) ($llm['text'] ?? '');

        $parsed = null;
        if (preg_match('/\{.*\}/s', $raw, $m)) $parsed = json_decode($m[0], true);

        if (! is_array($parsed)) {
            // LLM not configured / failed → graceful fallback: show top 3 candidates by rating
            $picks = array_slice($candidates, 0, 3);
            return $this->response->setJSON([
                'ok' => true,
                'reply' => 'Here are some popular picks that match what you\'re looking for.',
                'picks' => array_map(fn ($p) => $this->hydrate($p, 'Highly rated by parents.'), $picks),
                'follow_up' => 'Want me to narrow down by age or budget?',
            ]);
        }

        // Map LLM-picked IDs back to full product rows
        $byId = [];
        foreach ($candidates as $c) $byId[(int) $c['id']] = $c;
        $picks = [];
        foreach (($parsed['picks'] ?? []) as $pick) {
            $row = $byId[(int) ($pick['id'] ?? 0)] ?? null;
            if ($row) $picks[] = $this->hydrate($row, $pick['why'] ?? '');
        }

        return $this->response->setJSON([
            'ok'        => true,
            'reply'     => $parsed['reply']     ?? 'Here are my picks for you.',
            'picks'     => $picks,
            'follow_up' => $parsed['follow_up'] ?? null,
        ]);
    }

    private function hydrate(array $p, string $why): array
    {
        $hero = $p['hero_image'];
        if ($hero && ! preg_match('#^https?://#', $hero)) $hero = base_url($hero);
        return [
            'name'  => $p['name'],
            'url'   => base_url('product/' . $p['slug']),
            'image' => $hero,
            'price' => $p['price'] ? '₹' . number_format(round($p['price']/100)) : '—',
            'why'   => $why,
        ];
    }
}
