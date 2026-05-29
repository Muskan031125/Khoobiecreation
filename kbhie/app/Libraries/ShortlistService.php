<?php

namespace App\Libraries;

use Config\Database;

/**
 * Per-session shortlist (a.k.a. wishlist). Works for anon + logged-in users alike.
 * For logged-in users this can later sync to the `wishlists` + `wishlist_items` tables.
 */
class ShortlistService
{
    private const KEY = 'shortlist';

    public function toggle(int $productId): array
    {
        if ($productId <= 0) return ['ok' => false, 'error' => 'Invalid product.'];
        $ids = $this->ids();
        $inList = in_array($productId, $ids, true);
        if ($inList) {
            $ids = array_values(array_diff($ids, [$productId]));
        } else {
            $ids = array_values(array_unique(array_merge([$productId], $ids)));
        }
        session()->set(self::KEY, $ids);
        return [
            'ok'        => true,
            'in_list'   => ! $inList,
            'count'     => count($ids),
        ];
    }

    public function remove(int $productId): array
    {
        $ids = array_values(array_diff($this->ids(), [$productId]));
        session()->set(self::KEY, $ids);
        return ['ok' => true, 'count' => count($ids)];
    }

    public function ids(): array
    {
        $v = session(self::KEY);
        return is_array($v) ? array_values(array_map('intval', $v)) : [];
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    /** Hydrate shortlisted products with everything _product_card needs. */
    public function list(int $limit = 50): array
    {
        $ids = $this->ids();
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

        // Preserve "most recently added first" order
        $byId = [];
        foreach ($rows as $r) $byId[(int) $r['id']] = $r;
        $out = [];
        foreach ($ids as $id) if (isset($byId[$id])) $out[] = $byId[$id];
        return $out;
    }
}
