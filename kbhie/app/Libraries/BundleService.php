<?php

namespace App\Libraries;

use Config\Database;

class BundleService
{
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    /** Bundles that include this product — surfaced on its PDP. */
    public function forProduct(int $productId): array
    {
        $rows = $this->db->table('bundle_items bi')
            ->select('b.id, b.slug, b.name, b.tagline, b.hero_image, b.bundle_price, b.items_total, b.savings')
            ->join('bundles b', 'b.id = bi.bundle_id')
            ->where('bi.product_id', $productId)
            ->where('b.is_active', 1)
            ->orderBy('b.sort_order', 'ASC')
            ->limit(3)->get()->getResultArray();
        return $rows;
    }

    /** Hydrate a single bundle with its items for the bundle PDP. */
    public function getWithItems(string $slug): ?array
    {
        $b = $this->db->table('bundles')->where('slug', $slug)->where('is_active', 1)->get()->getRowArray();
        if (! $b) return null;

        $b['items'] = $this->db->table('bundle_items bi')
            ->select('bi.qty, bi.role, p.id, p.slug, p.name, p.type, p.hero_image, p.short_desc,
                      v.price, v.compare_at_price', false)
            ->join('products p', 'p.id = bi.product_id')
            ->join('product_variants v', 'v.id = bi.variant_id OR (bi.variant_id IS NULL AND v.product_id = p.id AND v.is_default = 1)', 'left')
            ->where('bi.bundle_id', $b['id'])
            ->orderBy('bi.sort_order', 'ASC')
            ->get()->getResultArray();

        return $b;
    }

    /** Recalculate cached items_total + savings from current variant prices. */
    public function recalculate(int $bundleId): void
    {
        $sum = (int) ($this->db->query("
            SELECT COALESCE(SUM(v.price * bi.qty), 0) AS s
            FROM bundle_items bi
            LEFT JOIN product_variants v ON v.id = bi.variant_id OR (bi.variant_id IS NULL AND v.product_id = bi.product_id AND v.is_default = 1)
            WHERE bi.bundle_id = ?
        ", [$bundleId])->getRow()->s ?? 0);

        $bundle = $this->db->table('bundles')->where('id', $bundleId)->get()->getRow();
        $savings = max(0, $sum - (int) ($bundle->bundle_price ?? 0));
        $this->db->table('bundles')->where('id', $bundleId)->update([
            'items_total' => $sum,
            'savings'     => $savings,
        ]);
    }
}
