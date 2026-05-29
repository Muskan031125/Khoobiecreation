<?php

namespace App\Modules\Api\Controllers;

use App\Models\ProductModel;
use Config\Database;

class ProductsController extends BaseApiController
{
    public function index()
    {
        $opts = [
            'category_slug' => $this->request->getGet('category'),
            'search'        => $this->request->getGet('q'),
            'age_min'       => $this->request->getGet('age_min'),
            'age_max'       => $this->request->getGet('age_max'),
            'price_min'     => $this->request->getGet('price_min'),
            'price_max'     => $this->request->getGet('price_max'),
            'sort'          => $this->request->getGet('sort') ?: 'featured',
            'page'          => (int) ($this->request->getGet('page') ?: 1),
            'per_page'      => min(50, (int) ($this->request->getGet('per_page') ?: 24)),
        ];
        $products = (new ProductModel())->listActive($opts);
        return $this->ok([
            'products' => $this->transform($products),
            'page'     => $opts['page'],
            'per_page' => $opts['per_page'],
        ]);
    }

    public function show(string $slug)
    {
        $model = new ProductModel();
        $product = $model->getBySlug($slug);
        if (! $product) return $this->fail('Product not found', 404);
        $product = $model->loadFull($product);
        return $this->ok(['product' => $this->oneFull($product)]);
    }

    public function categories()
    {
        $rows = Database::connect()->query(
            "SELECT id, parent_id, slug, name, sort_order, icon FROM categories
             WHERE is_active = 1
             ORDER BY (parent_id IS NULL) DESC, sort_order ASC, name ASC"
        )->getResultArray();
        return $this->ok(['categories' => $rows]);
    }

    private function transform(array $rows): array
    {
        return array_map(function ($p) {
            $hero = $p['hero_image'] ?? null;
            if ($hero && ! preg_match('#^https?://#', $hero)) $hero = base_url($hero);
            return [
                'id'           => (int) $p['id'],
                'sku'          => $p['sku'],
                'slug'         => $p['slug'],
                'name'         => $p['name'],
                'short_desc'   => $p['short_desc'] ?? null,
                'image'        => $hero,
                'price_paise'  => (int) ($p['price'] ?? 0),
                'mrp_paise'    => $p['compare_at_price'] ? (int) $p['compare_at_price'] : null,
                'age_min'      => (int) ($p['age_min_years'] ?? 0),
                'age_max'      => (int) ($p['age_max_years'] ?? 0),
                'rating'       => (float) ($p['rating_avg'] ?? 0),
                'rating_count' => (int) ($p['rating_count'] ?? 0),
                'variant_id'   => (int) ($p['variant_id'] ?? 0),
                'in_stock'     => isset($p['total_stock']) ? ((int) $p['total_stock'] > 0) : null,
            ];
        }, $rows);
    }

    private function oneFull(array $p): array
    {
        $hero = $p['hero_image'] ?? null;
        if ($hero && ! preg_match('#^https?://#', $hero)) $hero = base_url($hero);
        $gallery = json_decode($p['gallery'] ?? '[]', true) ?: [];
        $gallery = array_map(fn ($g) => preg_match('#^https?://#', $g) ? $g : base_url($g), $gallery);
        return [
            'id'           => (int) $p['id'],
            'sku'          => $p['sku'],
            'slug'         => $p['slug'],
            'name'         => $p['name'],
            'type'         => $p['type'],
            'short_desc'   => $p['short_desc'] ?? null,
            'long_desc'    => $p['long_desc']  ?? null,
            'image'        => $hero,
            'gallery'      => $gallery,
            'video_url'    => $p['video_url'] ?? null,
            'age_min'      => (int) ($p['age_min_years'] ?? 0),
            'age_max'      => (int) ($p['age_max_years'] ?? 0),
            'rating'       => (float) $p['rating_avg'],
            'rating_count' => (int) $p['rating_count'],
            'total_stock'  => (int) ($p['total_stock'] ?? 0),
            'variants'     => array_map(fn ($v) => [
                'id'         => (int) $v['id'],
                'name'       => $v['name'],
                'price_paise'=> (int) $v['price'],
                'mrp_paise'  => $v['compare_at_price'] ? (int) $v['compare_at_price'] : null,
            ], $p['variants'] ?? []),
            'categories'   => array_map(fn ($c) => ['name' => $c['name'], 'slug' => $c['slug']], $p['categories'] ?? []),
            'reviews_top3' => array_slice(array_map(fn ($r) => [
                'rating' => (int) $r['rating'],
                'title'  => $r['title'],
                'body'   => $r['body'],
                'name'   => $r['reviewer_name'],
                'date'   => $r['created_at'],
            ], $p['reviews'] ?? []), 0, 3),
        ];
    }
}
