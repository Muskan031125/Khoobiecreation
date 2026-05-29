<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

class ProductsController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();
        $req = $this->request;
        $q       = trim((string) $req->getGet('q'));
        $status  = $req->getGet('status') ?: '';
        $page    = max(1, (int) $req->getGet('page'));
        $perPage = (int) ($req->getGet('per_page') ?? 25);
        if (! in_array($perPage, [25, 50, 100, 250], true)) $perPage = 25;

        $sort    = $req->getGet('sort') ?: 'id';
        $sortDir = strtoupper($req->getGet('dir') ?: 'DESC');
        if (! in_array($sortDir, ['ASC','DESC'], true)) $sortDir = 'DESC';
        $allowedSort = ['id','sku','name','status','type','price','stock'];
        if (! in_array($sort, $allowedSort, true)) $sort = 'id';
        $sortCol = match ($sort) {
            'price' => 'v.price',
            'stock' => 'stock',
            default => 'p.' . $sort,
        };

        $b = $db->table('products p')
            ->select('p.id, p.sku, p.name, p.status, p.type, p.is_featured, p.partner_id, p.hero_image, v.price, (SELECT SUM(qty_on_hand) FROM inventory i WHERE i.variant_id IN (SELECT id FROM product_variants WHERE product_id = p.id)) AS stock')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->where('p.deleted_at', null);
        if ($q)      $b->groupStart()->like('p.name', $q)->orLike('p.sku', $q)->groupEnd();
        if ($status) $b->where('p.status', $status);

        $total = (int) $b->countAllResults(false);
        $products = $b->orderBy($sortCol, $sortDir)
                      ->limit($perPage, ($page - 1) * $perPage)
                      ->get()->getResultArray();

        return $this->view('App\Modules\Admin\Views\products_index', [
            'page'           => ['title' => 'Products'],
            'products'       => $products,
            'q'              => $q,
            'status'         => $status,
            'sort'           => $sort,
            'sortDir'        => $sortDir,
            'currentPage'    => $page,
            'perPage'        => $perPage,
            'perPageOptions' => [25, 50, 100, 250],
            'totalRows'      => $total,
            'totalPages'     => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function new()
    {
        return $this->edit(null);
    }

    public function edit($id = null)
    {
        $db = Database::connect();
        $product = null;
        $variant = null;
        if ($id) {
            $product = $db->table('products')->where('id', $id)->get()->getRowArray();
            if (! $product) return redirect()->to('/admin/products')->with('error', 'Product not found.');
            $variant = $db->table('product_variants')->where('product_id', $id)->where('is_default', 1)->get()->getRowArray();
        }
        $categories = $db->table('categories')->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray();
        $taxClasses = $db->table('tax_classes')->where('is_active', 1)->get()->getResultArray();
        $partners   = $db->table('partners')->where('status', 'active')->get()->getResultArray();
        $selectedCats = $id ? array_column($db->table('product_categories')->where('product_id', $id)->get()->getResultArray(), 'category_id') : [];
        return $this->view('App\Modules\Admin\Views\products_edit', [
            'page' => ['title' => $id ? 'Edit Product' : 'New Product'],
            'product' => $product,
            'variant' => $variant,
            'categories' => $categories,
            'taxClasses' => $taxClasses,
            'partners' => $partners,
            'selectedCats' => $selectedCats,
        ]);
    }

    public function show($id)         { return redirect()->to('/admin/products/' . $id . '/edit'); }
    public function create()          { return $this->save(null); }
    public function update($id)       { return $this->save($id); }

    public function save($id = null)
    {
        $db = Database::connect();
        $p = $this->request->getPost();
        $data = [
            'sku'             => $p['sku'],
            'slug'            => $p['slug'] ?: url_title(strtolower($p['name']), '-'),
            'name'            => $p['name'],
            'type'            => $p['type']        ?? 'simple',
            'short_desc'      => $p['short_desc']  ?? null,
            'long_desc'       => $p['long_desc']   ?? null,
            'hero_image'      => $p['hero_image']  ?? null,
            'status'          => $p['status']      ?? 'draft',
            'is_featured'     => isset($p['is_featured']) ? 1 : 0,
            'tax_class_id'    => $p['tax_class_id'] ?: null,
            'hsn_code'        => $p['hsn_code']    ?? null,
            'partner_id'      => $p['partner_id']  ?: null,
            'age_min_years'   => $p['age_min_years'] ?: null,
            'age_max_years'   => $p['age_max_years'] ?: null,
            'seo_title'       => $p['seo_title']   ?? null,
            'seo_description' => $p['seo_description'] ?? null,
        ];
        if (! $id) {
            $data['published_at'] = $data['status'] === 'active' ? date('Y-m-d H:i:s') : null;
            $db->table('products')->insert($data);
            $id = (int) $db->insertID();
            // Default variant
            $db->table('product_variants')->insert([
                'product_id' => $id,
                'sku'        => $p['sku'] . '-DEFAULT',
                'name'       => 'Default',
                'price'      => (int) round(((float) ($p['price'] ?? 0)) * 100),
                'compare_at_price' => $p['compare_at'] ? (int) round(((float) $p['compare_at']) * 100) : null,
                'is_default' => 1,
                'is_active'  => 1,
            ]);
        } else {
            $db->table('products')->where('id', $id)->update($data);
            $variant = $db->table('product_variants')->where('product_id', $id)->where('is_default', 1)->get()->getRowArray();
            if ($variant) {
                $db->table('product_variants')->where('id', $variant['id'])->update([
                    'price' => (int) round(((float) ($p['price'] ?? 0)) * 100),
                    'compare_at_price' => $p['compare_at'] ? (int) round(((float) $p['compare_at']) * 100) : null,
                ]);
            }
        }
        // Categories M2M
        $db->table('product_categories')->where('product_id', $id)->delete();
        foreach ((array) ($p['categories'] ?? []) as $catId) {
            $db->table('product_categories')->insert(['product_id' => $id, 'category_id' => (int) $catId]);
        }
        return redirect()->to('/admin/products/' . $id . '/edit')->with('success', 'Product saved.');
    }

    public function delete($id)
    {
        Database::connect()->table('products')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/admin/products')->with('success', 'Product deleted.');
    }
}
