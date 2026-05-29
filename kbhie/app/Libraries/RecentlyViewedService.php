<?php

namespace App\Libraries;

use Config\Database;

/**
 * Tracks the last N products a visitor opened.
 * Storage: session (survives the browser session; works for anon + logged-in users).
 * Could be extended with a `recently_viewed` table + cookie sync for cross-device persistence.
 */
class RecentlyViewedService
{
    private const KEY = 'recently_viewed';
    private const MAX = 20;

    /** Push a product to the top of the recency list (idempotent — moves to front if already present). */
    public function track(int $productId): void
    {
        if ($productId <= 0) return;
        $list = $this->ids();
        // Drop any prior occurrence so it bubbles back to position 0
        $list = array_values(array_diff($list, [$productId]));
        array_unshift($list, $productId);
        $list = array_slice($list, 0, self::MAX);
        session()->set(self::KEY, $list);
    }

    /** Get the current id list, most-recent first. */
    public function ids(): array
    {
        $list = session(self::KEY);
        if (! is_array($list)) return [];
        return array_values(array_map('intval', $list));
    }

    /**
     * Hydrate recent products with everything _product_card.php needs, including badge data.
     * Preserves recency order. Excludes a given id (typically the current PDP).
     */
    public function list(int $limit = 8, ?int $excludeId = null): array
    {
        $ids = $this->ids();
        if ($excludeId) $ids = array_values(array_diff($ids, [$excludeId]));
        if (empty($ids)) return [];
        $ids = array_slice($ids, 0, $limit);

        $db = Database::connect();
        $rows = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years,
                      p.rating_avg, p.rating_count, p.sales_count, p.is_featured, p.published_at, p.created_at,
                      (SELECT id FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS variant_id,
                      (SELECT price FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS price,
                      (SELECT compare_at_price FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS compare_at_price,
                      (SELECT COALESCE(SUM(i.qty_on_hand),0) FROM inventory i
                         JOIN product_variants v2 ON v2.id = i.variant_id
                         WHERE v2.product_id = p.id) AS total_stock", false)
            ->whereIn('p.id', $ids)
            ->where('p.status', 'active')
            ->get()->getResultArray();

        // Re-order by recency
        $byId = [];
        foreach ($rows as $r) $byId[(int) $r['id']] = $r;
        $out = [];
        foreach ($ids as $id) if (isset($byId[$id])) $out[] = $byId[$id];
        return $out;
    }
}
