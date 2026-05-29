<?php

namespace App\Modules\Storefront\Controllers;

use App\Models\ProductModel;
use Config\Database;

class CatalogController extends BaseStoreController
{
    public function index()
    {
        return $this->renderListing(null);
    }

    public function category(string $slug)
    {
        $cat = Database::connect()->table('categories')->where('slug', $slug)->where('is_active', 1)->get()->getRowArray();
        if (! $cat) return redirect()->to('/shop');
        return $this->renderListing($cat);
    }

    protected function renderListing(?array $category)
    {
        $req = $this->request;
        $opts = [
            'category_slug' => $category['slug'] ?? null,
            'search'        => $req->getGet('q')         ?: null,
            'age_min'       => $req->getGet('age_min')   ?: null,
            'age_max'       => $req->getGet('age_max')   ?: null,
            'price_min'     => $req->getGet('price_min') ?: null,
            'price_max'     => $req->getGet('price_max') ?: null,
            'level'         => $req->getGet('level')     ?: null,   // beginner/intermediate/advanced for classes
            'modality'      => $req->getGet('modality')  ?: null,   // online/offline/hybrid
            'city'          => $req->getGet('city')      ?: null,   // meetups only
            'locality'      => $req->getGet('locality')  ?: null,   // meetups only
            'rating_min'    => $req->getGet('rating_min')?: null,
            'in_stock'      => $req->getGet('in_stock')  ? 1 : null,
            'sort'          => $req->getGet('sort')      ?: 'featured',
            'page'          => (int) ($req->getGet('page') ?: 1),
            'per_page'      => 24,
        ];

        // ---- What filter family does this category belong to?
        //      Drives which filter UI shows + which DB filters are applicable.
        $filterFamily = $this->detectFilterFamily($category);
        $opts['filter_family'] = $filterFamily;

        // Location-aware default: if browsing meetups family and no explicit city in URL,
        // auto-apply user's chosen city so listings show "near you" by default.
        if ($filterFamily === 'meetups' && empty($opts['city'])) {
            $loc = \App\Libraries\LocationService::current();
            if ($loc && ! empty($loc['city'])) $opts['city'] = $loc['city'];
        }

        $model      = new ProductModel();
        $products   = $model->listActive($opts);
        $categories = Database::connect()->table('categories')->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray();

        // Facets: pre-compute available cities/localities for the meetup family.
        $facets = $this->buildFacets($filterFamily, $category);

        $title = $category ? $category['name'] . ' — Krafty Khoobie' : 'Shop — Krafty Khoobie';
        $desc  = $category['description'] ?? 'Screen-free products, board games, books, kits and digital activities.';

        return $this->view('App\Modules\Storefront\Views\catalog', [
            'page' => array_merge($this->data['page'], [
                'title' => $title,
                'description' => $desc,
            ]),
            'category'      => $category,
            'categories'    => $categories,
            'products'      => $products,
            'filters'       => $opts,
            'filter_family' => $filterFamily,
            'facets'        => $facets,
        ]);
    }

    /**
     * 'meetups'   → city, locality, date filters
     * 'classes'   → level, age, modality, days, instructor
     * 'physical'  → price, age, rating, stock
     * 'digital'   → price, rating
     * 'affiliate' → partner, price
     * 'all'       → default minimal set
     */
    private function detectFilterFamily(?array $category): string
    {
        if (! $category) return 'all';
        $slug = $category['slug'];
        if (in_array($slug, ['local-meetups'], true))                 return 'meetups';
        if (in_array($slug, ['classes','creative-classes','mindsport-classes','activity-classes'], true)) return 'classes';
        if ($slug === 'digital')                                       return 'digital';
        // Walk up — children of 'local-meetups' inherit meetups, etc.
        if ($category['parent_id']) {
            $parent = Database::connect()->table('categories')->where('id', $category['parent_id'])->get()->getRowArray();
            if ($parent && in_array($parent['slug'], ['local-meetups'], true)) return 'meetups';
            if ($parent && in_array($parent['slug'], ['classes'], true))       return 'classes';
        }
        return 'physical';
    }

    private function buildFacets(string $family, ?array $category): array
    {
        $db = Database::connect();
        $facets = [];
        if ($family === 'meetups') {
            // List distinct cities and (per active city) localities for filter dropdown
            $facets['cities'] = $db->table('meetups m')
                ->select('city, COUNT(*) AS n')
                ->where('city IS NOT NULL', null, false)
                ->groupBy('city')->orderBy('n', 'DESC')
                ->get()->getResultArray();
            $facets['localities'] = $db->table('meetups m')
                ->select('city, locality, COUNT(*) AS n')
                ->where('locality IS NOT NULL', null, false)
                ->groupBy('city, locality')->orderBy('city')->orderBy('n', 'DESC')
                ->get()->getResultArray();
        }
        if ($family === 'classes') {
            $facets['levels']    = ['beginner', 'intermediate', 'advanced'];
            $facets['modality']  = ['online', 'offline', 'hybrid'];
        }
        if ($family === 'physical') {
            $facets['priceBuckets'] = [
                ['label' => 'Under ₹500',    'max' => 50000],
                ['label' => '₹500–₹1,500',   'min' => 50000,  'max' => 150000],
                ['label' => '₹1,500–₹3,000', 'min' => 150000, 'max' => 300000],
                ['label' => 'Above ₹3,000',  'min' => 300000],
            ];
        }
        return $facets;
    }
}
