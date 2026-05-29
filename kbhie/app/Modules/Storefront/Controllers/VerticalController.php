<?php

namespace App\Modules\Storefront\Controllers;

use App\Models\ProductModel;
use Config\Database;

/**
 * Per-vertical SEO landing pages — custom heroes, JSON-LD, and curated grids.
 * Different from generic /shop/{category} pages: these target intent keywords
 * like "online classes for kids india" or "kids workshops in mumbai".
 */
class VerticalController extends BaseStoreController
{
    public function classes()
    {
        $db = Database::connect();
        $tuitions = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years, p.rating_avg, p.rating_count, p.sales_count, p.is_featured, p.published_at, p.created_at,
                      v.id AS variant_id, v.price, v.compare_at_price,
                      t.instructor_name, t.modality", false)
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('tuitions t', 't.product_id = p.id', 'left')
            ->where('p.type', 'tuition')->where('p.status', 'active')
            ->orderBy('p.sales_count', 'DESC')->limit(16)->get()->getResultArray();

        $courses = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years, p.rating_avg, p.rating_count, p.sales_count, p.is_featured,
                      v.id AS variant_id, v.price, v.compare_at_price,
                      c.instructor_name, c.lessons_count, c.total_minutes", false)
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('courses c', 'c.product_id = p.id', 'left')
            ->where('p.type', 'course')->where('p.status', 'active')
            ->orderBy('p.sales_count', 'DESC')->limit(8)->get()->getResultArray();

        return $this->view('App\Modules\Storefront\Views\vertical_classes', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'Online live classes & courses for kids — Khoobie',
                'description' => 'Live online tuition, self-paced courses, weekend workshops for kids — chess, abacus, Vedic maths, calligraphy, music, more. Free trial classes available.',
            ]),
            'tuitions' => $tuitions,
            'courses'  => $courses,
        ]);
    }

    public function meetups()
    {
        $db = Database::connect();
        $upcoming = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years, p.rating_avg, p.rating_count, p.sales_count, p.is_featured,
                      v.id AS variant_id, v.price, v.compare_at_price,
                      m.city, m.locality, m.area, m.starts_at, m.capacity, m.rsvp_count, m.is_free", false)
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('meetups m', 'm.product_id = p.id')
            ->where('p.type', 'meetup')->where('p.status', 'active')
            ->where('m.starts_at >=', date('Y-m-d H:i:s'))
            ->orderBy('m.starts_at', 'ASC')->limit(20)->get()->getResultArray();

        $cities = $db->table('meetups')
            ->select('city, COUNT(*) AS n')
            ->where('starts_at >=', date('Y-m-d H:i:s'))
            ->groupBy('city')->orderBy('n', 'DESC')->get()->getResultArray();

        return $this->view('App\Modules\Storefront\Views\vertical_meetups', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'In-person kids workshops & meetups across India — Khoobie',
                'description' => 'Find weekend kids workshops near you — pottery, art, swimming, karate, skating — in Mumbai, Bangalore, Delhi, Pune and more.',
            ]),
            'upcoming' => $upcoming,
            'cities'   => $cities,
        ]);
    }

    public function digital()
    {
        $db = Database::connect();
        $digitals = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years, p.rating_avg, p.rating_count, p.sales_count, p.is_featured,
                      v.id AS variant_id, v.price, v.compare_at_price", false)
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->where('p.type', 'digital')->where('p.status', 'active')
            ->limit(24)->get()->getResultArray();

        return $this->view('App\Modules\Storefront\Views\vertical_digital', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'Instant-download printables, ebooks & digital kits — Khoobie',
                'description' => 'Printable worksheets, activity sheets, and digital learning packs for kids — download instantly, no shipping wait.',
            ]),
            'digitals' => $digitals,
        ]);
    }

    public function affiliate()
    {
        $db = Database::connect();
        $picks = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years, p.rating_avg, p.rating_count, p.sales_count, p.is_featured,
                      v.id AS variant_id, v.price, v.compare_at_price,
                      a.partner_name", false)
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->join('affiliate_links a', 'a.product_id = p.id', 'left')
            ->where('p.type', 'affiliate')->where('p.status', 'active')
            ->orderBy('p.rating_avg', 'DESC')->limit(24)->get()->getResultArray();

        return $this->view('App\Modules\Storefront\Views\vertical_affiliate', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'Editor-picked kids products from across the internet — Khoobie',
                'description' => 'Hand-curated kids products from Amazon, Flipkart and trusted brands — vetted by the Khoobie editorial team.',
            ]),
            'picks' => $picks,
        ]);
    }
}
