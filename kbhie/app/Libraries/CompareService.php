<?php

namespace App\Libraries;

use Config\Database;

/**
 * Per-session compare list. Capped at MAX items (industry-standard 4).
 */
class CompareService
{
    private const KEY = 'compare';
    public  const MAX = 4;

    public function toggle(int $productId): array
    {
        if ($productId <= 0) return ['ok' => false, 'error' => 'Invalid product.'];
        $ids = $this->ids();
        $inList = in_array($productId, $ids, true);

        if ($inList) {
            $ids = array_values(array_diff($ids, [$productId]));
            session()->set(self::KEY, $ids);
            return ['ok' => true, 'in_list' => false, 'count' => count($ids), 'max' => self::MAX];
        }

        if (count($ids) >= self::MAX) {
            return ['ok' => false, 'error' => 'Compare can hold up to ' . self::MAX . ' products. Remove one to add another.', 'count' => count($ids), 'max' => self::MAX, 'in_list' => false];
        }

        $ids[] = $productId;
        session()->set(self::KEY, $ids);
        return ['ok' => true, 'in_list' => true, 'count' => count($ids), 'max' => self::MAX];
    }

    public function remove(int $productId): array
    {
        $ids = array_values(array_diff($this->ids(), [$productId]));
        session()->set(self::KEY, $ids);
        return ['ok' => true, 'count' => count($ids), 'max' => self::MAX];
    }

    public function clear(): array
    {
        session()->set(self::KEY, []);
        return ['ok' => true, 'count' => 0, 'max' => self::MAX];
    }

    public function ids(): array
    {
        $v = session(self::KEY);
        return is_array($v) ? array_values(array_map('intval', $v)) : [];
    }

    public function count(): int { return count($this->ids()); }
    public function has(int $productId): bool { return in_array($productId, $this->ids(), true); }

    /**
     * Hydrate compare products with the full attribute set needed by the comparison table.
     * Returns one entry per id: product row + variants + categories + attributes + total_stock.
     */
    public function list(): array
    {
        $ids = $this->ids();
        if (empty($ids)) return [];

        $db = Database::connect();
        $rows = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.type, p.age_min_years, p.age_max_years,
                      p.rating_avg, p.rating_count, p.sales_count, p.is_featured, p.published_at, p.created_at,
                      (SELECT id FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS variant_id,
                      (SELECT price FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS price,
                      (SELECT compare_at_price FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS compare_at_price,
                      (SELECT COALESCE(SUM(i.qty_on_hand),0) FROM inventory i
                         JOIN product_variants v2 ON v2.id = i.variant_id WHERE v2.product_id = p.id) AS total_stock", false)
            ->whereIn('p.id', $ids)
            ->where('p.status', 'active')
            ->get()->getResultArray();

        // Index + preserve insertion order
        $byId = [];
        foreach ($rows as $r) $byId[(int) $r['id']] = $r;

        $items = [];
        foreach ($ids as $id) {
            if (! isset($byId[$id])) continue;
            $p = $byId[$id];

            // Primary category + attributes for the comparison rows
            $p['category'] = $db->table('categories c')
                ->select('c.name, c.slug')
                ->join('product_categories pc', 'pc.category_id = c.id')
                ->where('pc.product_id', $id)
                ->orderBy('c.parent_id', 'ASC')
                ->limit(1)->get()->getRowArray();

            $p['attributes'] = $db->table('product_attributes')
                ->select('name, value')
                ->where('product_id', $id)
                ->orderBy('sort_order')
                ->get()->getResultArray();

            $items[] = $p;
        }
        return $items;
    }
}
