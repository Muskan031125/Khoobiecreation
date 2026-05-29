<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class ProductModel extends Model
{
    protected $table         = 'products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField  = 'deleted_at';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'sku','slug','name','type','short_desc','long_desc','hero_image','gallery','video_url',
        'status','is_featured','partner_id','tax_class_id','hsn_code',
        'age_min_years','age_max_years','rating_avg','rating_count','sales_count',
        'seo_title','seo_description','schema_org','rich_blocks','meta','published_at',
    ];

    public function listActive(array $opts = []): array
    {
        $db = Database::connect();
        $b = $db->table('products p')
            // p.type + p.partner_id are REQUIRED for the card to route enrol-only types
            // (course/tuition/meetup/service/membership) to /enrol/{vid} and to render
            // affiliate "Buy on Amazon" CTAs. Without them every card falls back to "simple".
            ->select("p.id, p.sku, p.slug, p.name, p.short_desc, p.hero_image, p.type, p.partner_id,
                      p.age_min_years, p.age_max_years,
                      p.rating_avg, p.rating_count, p.sales_count, p.is_featured, p.published_at, p.created_at,
                      v.id AS variant_id, v.price, v.compare_at_price,
                      (SELECT COALESCE(SUM(i.qty_on_hand), 0) FROM inventory i
                         JOIN product_variants v2 ON v2.id = i.variant_id
                         WHERE v2.product_id = p.id) AS total_stock", false)
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->where('p.status', 'active')
            ->where('p.deleted_at', null);

        if (! empty($opts['category_slug'])) {
            $b->join('product_categories pc', 'pc.product_id = p.id')
              ->join('categories c', 'c.id = pc.category_id')
              ->where('c.slug', $opts['category_slug']);
        }
        if (! empty($opts['search'])) {
            $term = trim($opts['search']);
            $b->groupStart()->like('p.name', $term)->orLike('p.short_desc', $term)->groupEnd();
        }
        if (! empty($opts['age_min']))   $b->where('p.age_max_years >=', (int) $opts['age_min']);
        if (! empty($opts['age_max'])) {
    $b->where('p.age_min_years <=', (int) $opts['age_max'])
      ->where('p.age_max_years <=', (int) $opts['age_max']);
}
        if (! empty($opts['price_min'])) $b->where('v.price >=', (int) $opts['price_min']);
        if (! empty($opts['price_max'])) $b->where('v.price <=', (int) $opts['price_max']);
        if (! empty($opts['rating_min']))$b->where('p.rating_avg >=', (float) $opts['rating_min']);
        if (! empty($opts['in_stock'])) {
            $b->where('(SELECT COALESCE(SUM(i.qty_on_hand),0) FROM inventory i
                          JOIN product_variants v3 ON v3.id = i.variant_id
                          WHERE v3.product_id = p.id) > 0', null, false);
        }

        // Class / tuition filters
        if (! empty($opts['level'])) {
            $b->join('courses ce', 'ce.product_id = p.id', 'left')
              ->groupStart()->where('ce.level', $opts['level'])->groupEnd();
        }
        if (! empty($opts['modality'])) {
            $b->join('tuitions tu', 'tu.product_id = p.id', 'left')
              ->groupStart()->where('tu.modality', $opts['modality'])->groupEnd();
        }
        // Meetup hyperlocal filters
        if (! empty($opts['city']) || ! empty($opts['locality'])) {
            $b->join('meetups me', 'me.product_id = p.id');
            if (! empty($opts['city']))     $b->where('me.city',     $opts['city']);
            if (! empty($opts['locality'])) $b->where('me.locality', $opts['locality']);
        }

        $sort = $opts['sort'] ?? 'featured';
        switch ($sort) {
            case 'price_asc':  $b->orderBy('v.price', 'ASC'); break;
            case 'price_desc': $b->orderBy('v.price', 'DESC'); break;
            case 'newest':     $b->orderBy('p.published_at', 'DESC'); break;
            case 'rating':     $b->orderBy('p.rating_avg', 'DESC'); break;
            case 'bestselling':$b->orderBy('p.sales_count', 'DESC'); break;
            default:           $b->orderBy('p.is_featured', 'DESC')->orderBy('p.id', 'DESC');
        }

        $perPage = (int) ($opts['per_page'] ?? 24);
        $page    = max(1, (int) ($opts['page'] ?? 1));
        $b->limit($perPage, ($page - 1) * $perPage);
        return $b->get()->getResultArray();
    }

    public function getBySlug(string $slug): ?array
    {
        $row = $this->where('slug', $slug)->where('status', 'active')->first();
        return $row ?: null;
    }

    public function loadFull(array $product): array
    {
        $db = Database::connect();
        $id = (int) $product['id'];

        $product['variants'] = $db->table('product_variants')
            ->where('product_id', $id)->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('id')
            ->get()->getResultArray();

        $product['attributes'] = $db->table('product_attributes')
            ->where('product_id', $id)
            ->orderBy('sort_order')->get()->getResultArray();

        $product['categories'] = $db->table('categories c')
            ->select('c.id, c.slug, c.name')
            ->join('product_categories pc', 'pc.category_id = c.id')
            ->where('pc.product_id', $id)->get()->getResultArray();

        // SELECT clause matches what _product_card.php expects (variant_id for Add-to-Cart,
        // rating/sales/published/stock for the data-driven status badge, short_desc + age pill).
        $cardCols = "p.id, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years,
                     p.rating_avg, p.rating_count, p.sales_count, p.is_featured, p.published_at, p.created_at,
                     v.id AS variant_id, v.price, v.compare_at_price,
                     (SELECT COALESCE(SUM(i.qty_on_hand),0) FROM inventory i
                        JOIN product_variants v2 ON v2.id = i.variant_id
                        WHERE v2.product_id = p.id) AS total_stock";

        $product['related'] = $db->table('products p')
            ->select($cardCols, false)
            ->join('product_related pr', 'pr.related_product_id = p.id')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->where('pr.product_id', $id)
            ->where('p.status', 'active')
            ->limit(8)->get()->getResultArray();

        // Fallback related: same category
        if (empty($product['related']) && ! empty($product['categories'])) {
            $catIds = array_column($product['categories'], 'id');
            $product['related'] = $db->table('products p')
                ->select($cardCols, false)
                ->join('product_categories pc', 'pc.product_id = p.id')
                ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
                ->whereIn('pc.category_id', $catIds)
                ->where('p.status', 'active')
                ->where('p.id !=', $id)
                ->limit(8)->get()->getResultArray();
        }

        $product['reviews'] = $db->table('reviews')
            ->where('product_id', $id)->where('status', 'published')
            ->orderBy('helpful_count', 'DESC')->orderBy('created_at', 'DESC')
            ->limit(10)->get()->getResultArray();

        $product['questions'] = $db->table('product_questions')
            ->where('product_id', $id)->where('is_published', 1)
            ->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray();

        // Tax + total stock across warehouses
        $product['total_stock'] = (int) ($db->table('inventory i')
            ->join('product_variants v', 'v.id = i.variant_id')
            ->where('v.product_id', $id)
            ->selectSum('i.qty_on_hand', 'q')
            ->get()->getRow()->q ?? 0);

        return $product;
    }
}
