<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\RecentlyViewedService;
use App\Libraries\Tracking\TrackingService;
use App\Models\ProductModel;

class ProductController extends BaseStoreController
{
    public function show(string $slug)
    {
        $model = new ProductModel();
        $product = $model->getBySlug($slug);
        if (! $product) return redirect()->to('/shop');

        $product = $model->loadFull($product);

        // Recently-viewed: push this product to the top of the recency list
        $recentSvc = new RecentlyViewedService();
        $recentSvc->track((int) $product['id']);

        // Server-side ViewContent event (mirrors client pixel for dedup recovery)
        try {
            $defaultVariant = $product['variants'][0] ?? null;
            (new TrackingService())->captureEvent([
                'event_name' => 'ViewContent',
                'value'      => $defaultVariant['price'] ?? null,
                'currency'   => 'INR',
                'url'        => current_url(),
                'source'     => 'server',
                'custom_data'=> [
                    'content_ids'  => [$product['sku']],
                    'content_name' => $product['name'],
                    'content_type' => 'product',
                ],
            ]);
        } catch (\Throwable $e) { /* swallow */ }

        $schema = $this->buildProductSchema($product);

        return $this->view('App\Modules\Storefront\Views\product', [
            'page' => array_merge($this->data['page'], [
                'title'       => $product['seo_title'] ?: ($product['name'] . ' — Krafty Khoobie'),
                'description' => $product['seo_description'] ?: $product['short_desc'],
                'image'       => base_url('og/product/' . $product['slug'] . '.png'),
                'type'        => 'product',
            ]),
            'product'        => $product,
            'schemaJson'     => json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'recentlyViewed' => $recentSvc->list(8, (int) $product['id']),
            'hideMobileCta'  => true,   // PDP renders its own sticky action bar
        ]);
    }

    public function reviewSubmit(string $slug)
    {
        $product = (new ProductModel())->getBySlug($slug);
        if (! $product) return redirect()->to('/shop');

        $rules = [
            'reviewer_name' => 'required|min_length[2]|max_length[150]',
            'rating'        => 'required|integer|in_list[1,2,3,4,5]',
            'body'          => 'required|min_length[10]|max_length[2000]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', 'Please share a rating and a few words.');
        }

        $user = session('user');
        \Config\Database::connect()->table('reviews')->insert([
            'product_id'        => $product['id'],
            'user_id'           => $user['id'] ?? null,
            'reviewer_name'     => $this->request->getPost('reviewer_name'),
            'reviewer_email'    => $user['email'] ?? null,
            'rating'            => (int) $this->request->getPost('rating'),
            'title'             => $this->request->getPost('title') ?: null,
            'body'              => $this->request->getPost('body'),
            'status'            => 'pending',
            'is_verified_buyer' => $user ? 1 : 0,
        ]);
        return redirect()->to('/product/' . $slug . '#write-review')
            ->with('success', 'Thanks! Your review is pending moderation and will appear within 24 hours.');
    }

    public function questionSubmit(string $slug)
    {
        $product = (new ProductModel())->getBySlug($slug);
        if (! $product) return redirect()->to('/shop');
        $user = session('user');
        \Config\Database::connect()->table('product_questions')->insert([
            'product_id'   => $product['id'],
            'user_id'      => $user['id'] ?? null,
            'asker_name'   => $this->request->getPost('asker_name') ?: ($user['name'] ?? 'Anonymous'),
            'question'     => $this->request->getPost('question'),
            'is_published' => 0,
        ]);
        return redirect()->to('/product/' . $slug)
            ->with('success', 'Question received — we will answer it via email and on this page within 24 hours.');
    }

    protected function buildProductSchema(array $product): array
    {
        $default = $product['variants'][0] ?? null;
        $offer = [
            '@type'    => 'Offer',
            'price'    => $default ? number_format($default['price'] / 100, 2, '.', '') : null,
            'priceCurrency' => 'INR',
            'availability'  => $product['total_stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'      => current_url(),
        ];
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product['name'],
            'description' => $product['short_desc'],
            'sku'         => $product['sku'],
            'image'       => $product['hero_image'] ? [base_url($product['hero_image'])] : [],
            'brand'       => ['@type' => 'Brand', 'name' => env('khoobie.brand_name', 'Krafty Khoobie')],
            'offers'      => $offer,
        ];
        if ((int) $product['rating_count'] > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $product['rating_avg'],
                'reviewCount' => $product['rating_count'],
            ];
        }
        return $schema;
    }
}
