<?php

namespace App\Modules\Partner\Controllers;

use Config\Database;

class ProductsController extends BasePartnerController
{
    public function index()
    {
        if (! $this->partner) return redirect()->to('/partner/login');
        $rows = Database::connect()->table('products p')
            ->select('p.id, p.sku, p.name, p.slug, p.status, p.hero_image, v.price,
                      (SELECT COALESCE(SUM(qty_on_hand),0) FROM inventory WHERE variant_id IN (SELECT id FROM product_variants WHERE product_id = p.id)) AS stock')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->where('p.partner_id', $this->partner['id'])->where('p.deleted_at', null)
            ->orderBy('p.id', 'DESC')->get()->getResultArray();
        return $this->view('App\Modules\Partner\Views\products_index', [
            'page' => ['title' => 'My Products'], 'rows' => $rows,
        ]);
    }

    public function new()
    {
        if (! $this->partner) return redirect()->to('/partner/login');
        return $this->view('App\Modules\Partner\Views\products_edit', [
            'page' => ['title' => 'New product'],
            'product' => null,
            'variant' => null,
        ]);
    }

    public function edit($id)
    {
        if (! $this->partner) return redirect()->to('/partner/login');
        $db = Database::connect();
        $product = $db->table('products')->where('id', (int) $id)->where('partner_id', $this->partner['id'])->get()->getRowArray();
        if (! $product) return redirect()->to('/partner/products');
        $variant = $db->table('product_variants')->where('product_id', $product['id'])->where('is_default', 1)->get()->getRowArray();
        return $this->view('App\Modules\Partner\Views\products_edit', [
            'page' => ['title' => 'Edit · ' . esc($product['name'])],
            'product' => $product, 'variant' => $variant,
        ]);
    }

    public function save()
    {
        if (! $this->partner) return redirect()->to('/partner/login');
        $rules = [
            'name'       => 'required|min_length[3]|max_length[200]',
            'short_desc' => 'permit_empty|max_length[500]',
            'long_desc'  => 'permit_empty',
            'price_inr'  => 'required|numeric|greater_than[0]',
            'mrp_inr'    => 'permit_empty|numeric',
            'stock_qty'  => 'permit_empty|integer|greater_than_equal_to[0]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = Database::connect();
        $req = $this->request;
        $productId = (int) $req->getPost('id');

        $slug = $req->getPost('slug') ?: url_title(strtolower($req->getPost('name')), '-', true);
        $sku  = $req->getPost('sku')  ?: 'KK-P' . $this->partner['id'] . '-' . strtoupper(substr(md5($req->getPost('name') . microtime()), 0, 6));

        $data = [
            'sku'             => $sku,
            'slug'            => $slug,
            'name'            => $req->getPost('name'),
            'type'            => $req->getPost('type') ?: 'simple',
            'short_desc'      => $req->getPost('short_desc'),
            'long_desc'       => $req->getPost('long_desc'),
            'hero_image'      => $req->getPost('hero_image'),
            'age_min_years'   => (int) $req->getPost('age_min_years') ?: null,
            'age_max_years'   => (int) $req->getPost('age_max_years') ?: null,
            'partner_id'      => $this->partner['id'],
            'status'          => 'draft',  // partners save as draft; admin approves
            'published_at'    => null,
        ];

        if ($productId) {
            // Edit: only allow if owned by this partner
            $owned = $db->table('products')->where('id', $productId)->where('partner_id', $this->partner['id'])->countAllResults();
            if (! $owned) return redirect()->to('/partner/products');
            $db->table('products')->where('id', $productId)->update($data);
        } else {
            // Ensure SKU unique
            while ($db->table('products')->where('sku', $sku)->countAllResults() > 0) {
                $sku = 'KK-P' . $this->partner['id'] . '-' . strtoupper(substr(md5(microtime() . rand()), 0, 6));
                $data['sku'] = $sku;
            }
            // Ensure slug unique
            $base = $slug; $n = 2;
            while ($db->table('products')->where('slug', $slug)->countAllResults() > 0) { $slug = $base . '-' . $n++; }
            $data['slug'] = $slug;

            $db->table('products')->insert($data);
            $productId = (int) $db->insertID();
        }

        // Default variant — upsert
        $price   = (int) round((float) $req->getPost('price_inr') * 100);
        $compare = (int) round((float) $req->getPost('mrp_inr')   * 100);
        $existing = $db->table('product_variants')->where('product_id', $productId)->where('is_default', 1)->get()->getRow();
        $varData = [
            'product_id'       => $productId,
            'sku'              => $sku . '-V1',
            'name'             => 'Default',
            'price'            => $price,
            'compare_at_price' => $compare > $price ? $compare : null,
            'is_default'       => 1,
            'is_active'        => 1,
        ];
        if ($existing) {
            $db->table('product_variants')->where('id', $existing->id)->update($varData);
            $variantId = (int) $existing->id;
        } else {
            $db->table('product_variants')->insert($varData);
            $variantId = (int) $db->insertID();
        }

        // Stock (uses default warehouse)
        $qty = (int) $req->getPost('stock_qty');
        $wh  = $db->table('warehouses')->where('is_default', 1)->get()->getRow();
        if ($wh) {
            $invExisting = $db->table('inventory')->where('variant_id', $variantId)->where('warehouse_id', $wh->id)->get()->getRow();
            if ($invExisting) {
                $db->table('inventory')->where('id', $invExisting->id)->update(['qty_on_hand' => $qty]);
            } else {
                $db->table('inventory')->insert(['variant_id' => $variantId, 'warehouse_id' => $wh->id, 'qty_on_hand' => $qty]);
            }
        }

        return redirect()->to('/partner/products')->with('success', '✓ Saved as draft. Khoobie admin will review and publish within 24h.');
    }
}
