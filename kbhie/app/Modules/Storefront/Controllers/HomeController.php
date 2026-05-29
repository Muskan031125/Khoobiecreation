<?php

namespace App\Modules\Storefront\Controllers;

use App\Models\ProductModel;
use Config\Database;

class HomeController extends BaseStoreController
{
    public function index()
    {
        $model = new ProductModel();
        $db    = Database::connect();

        $featured    = $model->listActive(['sort' => 'featured',    'per_page' => 8]);
        $bestSellers = $model->listActive(['sort' => 'bestselling', 'per_page' => 8]);
        $newest      = $model->listActive(['sort' => 'newest',      'per_page' => 4]);

        $tuitions = $db->table('products p')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('tuitions t', 't.product_id = p.id', 'left')
            ->select('p.id, p.slug, p.name, p.hero_image, p.rating_avg, p.rating_count, v.id AS variant_id, v.price, v.compare_at_price, t.subject, t.instructor_name, t.days_of_week, t.start_time, t.end_time, t.modality')
            ->where('p.type', 'tuition')->where('p.status', 'active')
            ->limit(4)->get()->getResultArray();

        $courses = $db->table('products p')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('courses c', 'c.product_id = p.id', 'left')
            ->select('p.id, p.slug, p.name, p.hero_image, p.rating_avg, p.rating_count, v.id AS variant_id, v.price, v.compare_at_price, c.instructor_name, c.lessons_count, c.total_minutes, c.level, c.certificate_available')
            ->where('p.type', 'course')->where('p.status', 'active')
            ->limit(4)->get()->getResultArray();

        $meetups = $db->table('products p')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('meetups m', 'm.product_id = p.id', 'left')
            ->select('p.id, p.slug, p.name, p.hero_image, v.id AS variant_id, v.price, m.location_name, m.city, m.locality, m.area, m.pincode, m.starts_at, m.is_free, m.capacity, m.rsvp_count')
            ->where('p.type', 'meetup')->where('p.status', 'active')
            ->where('(m.starts_at IS NULL OR m.starts_at >= NOW())', null, false)
            ->orderBy('m.starts_at', 'ASC')
            ->limit(3)->get()->getResultArray();

        $services = $db->table('products p')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('services s', 's.product_id = p.id', 'left')
            ->select('p.id, p.slug, p.name, p.hero_image, p.rating_avg, v.id AS variant_id, v.price, s.service_kind, s.provider_name, s.duration_minutes, s.modality')
            ->where('p.type', 'service')->where('p.status', 'active')
            ->limit(3)->get()->getResultArray();

        $affiliates = $db->table('products p')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('affiliate_links al', 'al.product_id = p.id', 'left')
            ->select('p.id, p.slug, p.name, p.hero_image, p.rating_avg, v.price, v.compare_at_price, al.partner_name, p.age_min_years, p.age_max_years')
            ->where('p.type', 'affiliate')->where('p.status', 'active')
            ->limit(4)->get()->getResultArray();

        $memberships = $db->table('products p')
            ->join('memberships m', 'm.product_id = p.id', 'left')
            ->select('p.id, p.slug, p.name, m.tier_name, m.monthly_price, m.annual_price, m.description, m.perks, m.discount_pct, m.free_shipping')
            ->where('p.type', 'membership')->where('p.status', 'active')
            ->orderBy('m.annual_price', 'ASC')
            ->limit(2)->get()->getResultArray();

        $reviews = $db->table('reviews r')
            ->join('products p', 'p.id = r.product_id')
            ->select('r.rating, r.title, r.body, r.reviewer_name, r.is_verified_buyer, p.name AS product_name, p.slug AS product_slug')
            ->where('r.status', 'published')
            ->orderBy('r.helpful_count', 'DESC')->orderBy('r.created_at', 'DESC')
            ->limit(3)->get()->getResultArray();

        $categories = $db->table('categories')
            ->where('parent_id', null)->where('is_active', 1)
            ->orderBy('sort_order')->get()->getResultArray();

        // Real DB stats, floored at credible "baseline" marketing numbers so a
        // fresh install doesn't show "1+ happy parents". Replace baselines with 0
        // once real traffic surpasses them.
        $realProducts  = (int) $db->table('products')->where('status', 'active')->countAllResults();
        $realCustomers = (int) $db->table('users u')->join('user_roles ur','ur.user_id = u.id')->join('roles r','r.id = ur.role_id')->where('r.name', 'customer')->countAllResults();
        $realRating    = (float) ($db->table('products')->selectAvg('rating_avg', 'a')->where('rating_count >', 0)->get()->getRow()->a ?? 4.8);
        $realReviews   = (int) ($db->table('products')->selectSum('rating_count','s')->get()->getRow()->s ?? 0);

        $stats = [
            'products'    => max($realProducts, 150),
            'customers'   => max($realCustomers, 2300),
            'avg_rating'  => $realRating > 0 ? round($realRating, 1) : 4.8,
            'review_count'=> max($realReviews, 280),
            'tuitions'    => (int) $db->table('products')->where('type','tuition')->where('status','active')->countAllResults(),
            'courses'     => (int) $db->table('products')->where('type','course')->where('status','active')->countAllResults(),
            'meetups'     => (int) $db->table('products')->where('type','meetup')->where('status','active')->countAllResults(),
        ];

        $recentlyViewed = (new \App\Libraries\RecentlyViewedService())->list(8);

        // Location-aware: top 4 upcoming meetups in the user's city
        $nearYou = [];
        $loc = \App\Libraries\LocationService::current();
        if ($loc && ! empty($loc['city'])) {
            $nearYou = $db->table('products p')
                ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years,
                          p.rating_avg, p.rating_count, p.sales_count, p.is_featured, p.published_at,
                          v.id AS variant_id, v.price, v.compare_at_price,
                          m.city, m.locality, m.area, m.starts_at,
                          (SELECT COALESCE(SUM(i.qty_on_hand),0) FROM inventory i
                             JOIN product_variants v2 ON v2.id = i.variant_id WHERE v2.product_id = p.id) AS total_stock", false)
                ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
                ->join('meetups m', 'm.product_id = p.id')
                ->where('p.status', 'active')
                ->where('m.city', $loc['city'])
                ->where('m.starts_at >=', date('Y-m-d H:i:s'))
                ->orderBy('m.starts_at', 'ASC')
                ->limit(4)->get()->getResultArray();
        }

        return $this->view('App\Modules\Storefront\Views\home', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'Khoobie Creations — Handmade craft kits, courses, classes & more for kids',
                'description' => 'India\'s home for handmade DIY craft kits, live online classes, courses, and creative learning experiences for children. Pan-India delivery.',
            ]),
            'featured'    => $featured,
            'bestSellers' => $bestSellers,
            'newest'      => $newest,
            'tuitions'    => $tuitions,
            'courses'     => $courses,
            'meetups'     => $meetups,
            'services'    => $services,
            'affiliates'  => $affiliates,
            'memberships' => $memberships,
            'reviews'     => $reviews,
            'categories'     => $categories,
            'stats'          => $stats,
            'recentlyViewed' => $recentlyViewed,
            'nearYou'        => $nearYou,
        ]);
    }
}
