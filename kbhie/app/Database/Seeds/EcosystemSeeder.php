<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds example products for every type in the Khoobie ecosystem so
 * admins and devs can see the full range of supported business models.
 *
 * Idempotent: each entry is added only if a product with that SKU doesn't exist.
 * Run repeatedly without duplicates.
 */
class EcosystemSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        $tax12 = $db->table('tax_classes')->where('slug', 'gst-12')->get()->getRow();
        $taxId = $tax12 ? (int) $tax12->id : null;
        $wh    = $db->table('warehouses')->where('is_default', 1)->get()->getRow();
        $whId  = $wh ? (int) $wh->id : null;

        $catId = function (string $slug) use ($db) {
            $r = $db->table('categories')->where('slug', $slug)->get()->getRow();
            return $r ? (int) $r->id : null;
        };

        // ============================================================
        // 1. AFFILIATE PRODUCTS  — link out to Amazon / Flipkart for commission
        // ============================================================
        $this->createAffiliate('KK-AFF-0001', 'Magic Sand 1 KG Pack — Sensory Play', 'Amazon', 'https://www.amazon.in/dp/B07XJ8C8YN?tag=khoobie-21', 12, 49900, 79900, $taxId, [$catId('arts')]);
        $this->createAffiliate('KK-AFF-0002', 'LEGO Classic Creative Bricks Box', 'Amazon', 'https://www.amazon.in/dp/B073RNNFGN?tag=khoobie-21', 8, 199900, 249900, $taxId, [$catId('arts')]);
        $this->createAffiliate('KK-AFF-0003', 'National Geographic Mineral Discovery Kit', 'Amazon', 'https://www.amazon.in/dp/B086CRYPV4?tag=khoobie-21', 10, 159900, 199900, $taxId, [$catId('nature')]);
        $this->createAffiliate('KK-AFF-0004', 'Junior Telescope 50mm Refractor', 'Flipkart', 'https://www.flipkart.com/item/p/khoobie-tag', 7, 249900, 349900, $taxId, [$catId('nature')]);
        $this->createAffiliate('KK-AFF-0005', 'KitchenAid Kids Cooking Apron', 'Amazon', 'https://www.amazon.in/dp/B08L5XZQX?tag=khoobie-21', 15, 89900, 119900, $taxId, [$catId('accessories')]);
        $this->createAffiliate('KK-AFF-0006', 'Wooden Chess Set Premium Edition', 'Amazon', 'https://www.amazon.in/dp/B07VPLPWXH?tag=khoobie-21', 10, 129900, 199900, $taxId, [$catId('arts')]);

        // ============================================================
        // 2. ONLINE COURSES — self-paced video curriculum
        // ============================================================
        $this->createCourse('KK-CRS-0001', 'Mandala Art Mastery for Kids', [
            'instructor' => 'Riya Kapoor', 'level' => 'beginner', 'lessons' => 12, 'minutes' => 360,
            'price' => 149900, 'compare' => 249900, 'age_min' => 8, 'age_max' => 14, 'hsn' => '9992',
            'what_youll_learn' => [
                '5 mandala styles from simple to advanced',
                'How to choose colour palettes that pop',
                'Creating your own mandala from a blank page',
                'Professional finishing techniques',
            ],
            'modules' => [
                ['title' => 'Welcome & supplies overview', 'lessons' => [
                    ['title' => 'Course introduction', 'minutes' => 5, 'preview' => 1],
                    ['title' => 'Supplies you will need',  'minutes' => 8, 'preview' => 1],
                ]],
                ['title' => 'Fundamentals',                 'lessons' => [
                    ['title' => 'Drawing the grid',             'minutes' => 20],
                    ['title' => 'Symmetry basics',              'minutes' => 25],
                    ['title' => 'Choosing your first pattern',  'minutes' => 18],
                ]],
                ['title' => 'Five Mandala Styles',           'lessons' => [
                    ['title' => 'Floral mandala',     'minutes' => 35],
                    ['title' => 'Geometric mandala',  'minutes' => 30],
                    ['title' => 'Mythology-inspired', 'minutes' => 40],
                    ['title' => 'Nature mandala',     'minutes' => 38],
                    ['title' => 'Free-form mandala',  'minutes' => 45],
                ]],
                ['title' => 'Finishing & sharing',           'lessons' => [
                    ['title' => 'Adding colour like a pro', 'minutes' => 28],
                    ['title' => 'Sealing & framing your art', 'minutes' => 12],
                ]],
            ],
        ], [$catId('arts')]);

        $this->createCourse('KK-CRS-0002', 'Vedic Math Junior — Lightning-fast Mental Math', [
            'instructor' => 'Arjun Mehta', 'level' => 'beginner', 'lessons' => 20, 'minutes' => 600,
            'price' => 249900, 'compare' => 399900, 'age_min' => 7, 'age_max' => 13, 'hsn' => '9992',
            'what_youll_learn' => [
                'Solve multi-digit multiplication in seconds',
                'Square any number ending in 5 instantly',
                'Verify answers using digital roots',
                'Build mental math confidence',
            ],
            'modules' => [
                ['title' => 'Introduction',           'lessons' => [['title' => 'Why Vedic math?', 'minutes' => 10, 'preview' => 1]]],
                ['title' => 'Quick multiplication',   'lessons' => [
                    ['title' => 'The Urdhva-Tiryagbyham trick', 'minutes' => 25],
                    ['title' => 'Drill 1 — 2-digit', 'minutes' => 20],
                ]],
                ['title' => 'Squaring numbers',       'lessons' => [['title' => 'Numbers ending in 5', 'minutes' => 18]]],
                ['title' => 'Division shortcuts',     'lessons' => [['title' => 'Nikhilam division', 'minutes' => 30]]],
            ],
        ], [$catId('arts')]);

        $this->createCourse('KK-CRS-0003', 'Indian Mythology Storytelling for Kids', [
            'instructor' => 'Anita Sharma', 'level' => 'all', 'lessons' => 8, 'minutes' => 240,
            'price' => 99900, 'compare' => 149900, 'age_min' => 5, 'age_max' => 12, 'hsn' => '9992',
            'what_youll_learn' => ['Story arcs of Mahabharata & Ramayana', 'Hidden lessons in folk tales', 'How to tell stories that captivate kids'],
            'modules' => [
                ['title' => 'Foundations', 'lessons' => [
                    ['title' => 'Why storytelling matters', 'minutes' => 12, 'preview' => 1],
                ]],
                ['title' => 'Great epics', 'lessons' => [
                    ['title' => 'The Ramayana — bite-size', 'minutes' => 35],
                    ['title' => 'The Mahabharata — bite-size', 'minutes' => 45],
                ]],
            ],
        ], [$catId('arts')]);

        // ============================================================
        // 3. LIVE TUITIONS — recurring weekly classes
        // ============================================================
        $this->createTuition('KK-TUT-0001', 'Weekly Art Class — Saturday Live', [
            'subject' => 'Art & Craft', 'grade' => 'All ages', 'instructor' => 'Riya Kapoor',
            'days' => ['Sat'], 'start' => '11:00:00', 'end' => '12:30:00',
            'modality' => 'online', 'max_students' => 20,
            'price' => 149900, 'compare' => 199900, 'age_min' => 6, 'age_max' => 14, 'hsn' => '9992',
            'billing' => 'monthly',
        ], [$catId('arts')]);

        $this->createTuition('KK-TUT-0002', 'Math Tuition — Class 5', [
            'subject' => 'Math', 'grade' => 'Class 5', 'instructor' => 'Arjun Mehta',
            'days' => ['Tue','Thu','Sat'], 'start' => '17:00:00', 'end' => '18:00:00',
            'modality' => 'online', 'max_students' => 15,
            'price' => 249900, 'compare' => 349900, 'age_min' => 9, 'age_max' => 11, 'hsn' => '9992',
            'billing' => 'monthly',
        ], [$catId('arts')]);

        $this->createTuition('KK-TUT-0003', 'Yoga for Kids — Morning Edition', [
            'subject' => 'Yoga & Wellness', 'grade' => 'All ages', 'instructor' => 'Meera Iyer',
            'days' => ['Mon','Wed','Fri'], 'start' => '06:30:00', 'end' => '07:15:00',
            'modality' => 'online', 'max_students' => 30,
            'price' => 99900, 'compare' => 149900, 'age_min' => 5, 'age_max' => 14, 'hsn' => '9992',
            'billing' => 'monthly',
        ], [$catId('nature')]);

        // ============================================================
        // 4. OFFLINE MEETUPS
        // ============================================================
        $this->createMeetup('KK-MTU-0001', 'Khoobie Craft Meetup — Noida Sector 18', [
            'location' => 'Khoobie HQ, Noida Sector 69', 'address' => 'B-110, Sector 69', 'city' => 'Noida', 'state' => 'Uttar Pradesh', 'pincode' => '201307',
            'lat' => 28.6045, 'lng' => 77.3729, 'maps' => 'https://maps.google.com/?q=Khoobie+Noida',
            'starts' => date('Y-m-d 11:00:00', strtotime('+14 days')),
            'ends'   => date('Y-m-d 14:00:00', strtotime('+14 days')),
            'capacity' => 40, 'free' => 1, 'host' => 'Khoobie team', 'host_phone' => '+91 88992 23300',
            'price' => 0, 'compare' => 0, 'age_min' => 4, 'age_max' => 12, 'hsn' => '9992',
            'agenda' => [
                ['time' => '11:00', 'item' => 'Welcome & supplies handed out'],
                ['time' => '11:30', 'item' => 'Group painting + craft station'],
                ['time' => '13:00', 'item' => 'Snack break (provided)'],
                ['time' => '13:30', 'item' => 'Show-and-tell + photo time'],
            ],
        ], [$catId('arts')]);

        $this->createMeetup('KK-MTU-0002', 'Mumbai Khoobie Parent-Child Painting Day', [
            'location' => 'Bandra Community Hall', 'address' => 'Hill Road, Bandra West', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400050',
            'lat' => 19.0596, 'lng' => 72.8295, 'maps' => 'https://maps.google.com/?q=Bandra+West+Mumbai',
            'starts' => date('Y-m-d 10:00:00', strtotime('+28 days')),
            'ends'   => date('Y-m-d 13:00:00', strtotime('+28 days')),
            'capacity' => 30, 'free' => 0, 'host' => 'Khoobie Mumbai chapter',
            'price' => 49900, 'compare' => 79900, 'age_min' => 5, 'age_max' => 12, 'hsn' => '9992',
            'agenda' => [
                ['time' => '10:00', 'item' => 'Check-in + welcome kit'],
                ['time' => '10:30', 'item' => 'Parent-child painting session'],
                ['time' => '12:30', 'item' => 'Lunch + take-home gifts'],
            ],
        ], [$catId('arts')]);

        // ============================================================
        // 5. 1-on-1 SERVICES
        // ============================================================
        $this->createService('KK-SRV-0001', '1-on-1 Math Tutoring — 60 min', [
            'kind' => 'tutoring', 'provider' => 'Arjun Mehta', 'duration' => 60,
            'modality' => 'online',
            'price' => 79900, 'compare' => 99900, 'age_min' => 6, 'age_max' => 14, 'hsn' => '9992',
            'slots' => 12, // create 12 example future slots
        ], [$catId('arts')]);

        $this->createService('KK-SRV-0002', 'Parent Consultation — 45 min', [
            'kind' => 'consultation', 'provider' => 'Dr. Anita Sharma', 'duration' => 45,
            'modality' => 'online',
            'price' => 99900, 'compare' => 149900, 'age_min' => 0, 'age_max' => 18, 'hsn' => '9992',
            'slots' => 8,
        ], [$catId('arts')]);

        $this->createService('KK-SRV-0003', 'Birthday Party Planning — Full Service', [
            'kind' => 'party_planning', 'provider' => 'Khoobie Events', 'duration' => 240,
            'modality' => 'at_home',
            'price' => 999900, 'compare' => 1499900, 'age_min' => 3, 'age_max' => 12, 'hsn' => '9992',
            'slots' => 0,
        ], [$catId('return-gifts')]);

        // ============================================================
        // 6. MEMBERSHIPS
        // ============================================================
        $this->createMembership('KK-MEM-0001', 'Khoobie Insider — Monthly', [
            'tier' => 'Insider', 'monthly' => 19900, 'annual' => 199900,
            'description' => 'For parents who Khoobie everything. Early access, 10% off every order, free shipping, free monthly course.',
            'perks' => [
                'Early access to all new launches',
                '10% off every order',
                'Free shipping (always, no minimum)',
                '1 free online course every month',
                '2× loyalty points on every purchase',
                'VIP customer support (WhatsApp priority line)',
            ],
            'discount' => 10, 'free_ship' => 1, 'early' => 1, 'free_courses' => 1, 'bonus' => 100,
            'price' => 19900, 'compare' => 29900, 'age_min' => 0, 'age_max' => 99, 'hsn' => '9992',
        ], [$catId('arts')]);

        $this->createMembership('KK-MEM-0002', 'Khoobie Family Pass — Annual', [
            'tier' => 'Family Pass', 'monthly' => 0, 'annual' => 999900,
            'description' => 'A whole year of Khoobie joy. Best for families with multiple children.',
            'perks' => [
                'Everything in Insider',
                'Two free craft kits each quarter',
                'Free entry to every Khoobie meetup',
                'Up to 4 children covered',
                'Birthday surprise box every year',
            ],
            'discount' => 15, 'free_ship' => 1, 'early' => 1, 'free_courses' => 1, 'bonus' => 200,
            'price' => 999900, 'compare' => 1599900, 'age_min' => 0, 'age_max' => 99, 'hsn' => '9992',
        ], [$catId('arts')]);
    }

    // ====================================================================
    // Helpers
    // ====================================================================

    protected function makeProduct(string $sku, string $name, string $type, int $price, int $compare, ?int $taxId, int $ageMin, int $ageMax, string $hsn, array $categoryIds, string $shortDesc = '', string $longDesc = ''): ?int
    {
        $db = $this->db;
        if ($db->table('products')->where('sku', $sku)->countAllResults() > 0) {
            return (int) $db->table('products')->where('sku', $sku)->get()->getRow()->id;
        }
        $slug = url_title(strtolower($name), '-', true);
        $hero = "https://picsum.photos/seed/{$slug}/900/900";
        $gallery = [
            "https://picsum.photos/seed/{$slug}-a/900/900",
            "https://picsum.photos/seed/{$slug}-b/900/900",
            "https://picsum.photos/seed/{$slug}-c/900/900",
            "https://picsum.photos/seed/{$slug}-d/900/900",
        ];
        $rich = [
            ['type' => 'usp_grid', 'items' => [
                ['icon' => '🎨', 'title' => 'Made with love',  'desc' => 'Curated by parents, loved by kids'],
                ['icon' => '🛡️','title' => 'Safe & vetted',  'desc' => 'Reviewed by the Khoobie team'],
                ['icon' => '↩️','title' => '7-day returns', 'desc' => 'No questions asked'],
                ['icon' => '🇮🇳','title' => 'Made in India','desc' => 'Pan-India delivery available'],
            ]],
        ];

        $db->table('products')->insert([
            'sku'           => $sku,
            'slug'          => $slug,
            'name'          => $name,
            'type'          => $type,
            'short_desc'    => $shortDesc,
            'long_desc'     => $longDesc,
            'hero_image'    => $hero,
            'gallery'       => json_encode($gallery),
            'status'        => 'active',
            'is_featured'   => 0,
            'tax_class_id'  => $taxId ?: null,
            'hsn_code'      => $hsn,
            'age_min_years' => $ageMin,
            'age_max_years' => $ageMax,
            'rating_avg'    => 4.7,
            'rating_count'  => random_int(8, 60),
            'sales_count'   => random_int(5, 200),
            'seo_title'     => $name . ' | Khoobie Creations',
            'seo_description' => substr($shortDesc, 0, 160),
            'rich_blocks'   => json_encode($rich),
            'published_at'  => date('Y-m-d H:i:s'),
        ]);
        $productId = (int) $db->insertID();

        foreach ($categoryIds as $cid) {
            if ($cid) $db->table('product_categories')->insert(['product_id' => $productId, 'category_id' => $cid]);
        }

        // Default variant — required by cart / pricing logic
        $db->table('product_variants')->insert([
            'product_id'       => $productId,
            'sku'              => $sku . '-V1',
            'name'             => 'Default',
            'price'            => $price,
            'compare_at_price' => $compare > $price ? $compare : null,
            'is_default'       => 1,
            'is_active'        => 1,
        ]);
        return $productId;
    }

    protected function createAffiliate(string $sku, string $name, string $partner, string $url, int $commissionPct, int $price, int $compare, ?int $taxId, array $cats): void
    {
        $desc = "Sold by {$partner}. Khoobie hand-picks the best, you check out on {$partner} — a small commission helps us keep the lights on. No extra cost to you.";
        $id = $this->makeProduct($sku, $name, 'affiliate', $price, $compare, $taxId, 3, 14, '9503', $cats, $desc, $desc);
        if (! $id) return;

        $this->db->table('affiliate_links')->ignore(true)->insert([
            'product_id'       => $id,
            'partner_name'     => $partner,
            'outbound_url'     => $url,
            'commission_pct'   => $commissionPct,
            'price_at_partner' => $price,
            'price_updated_at' => date('Y-m-d H:i:s'),
            'is_active'        => 1,
        ]);
    }

    protected function createCourse(string $sku, string $name, array $c, array $cats): void
    {
        $shortDesc = "{$c['lessons']} on-demand lessons · " . round($c['minutes'] / 60, 1) . " hours · taught by {$c['instructor']}";
        $longDesc  = "A self-paced course you can take from anywhere. " . count($c['modules']) . " modules, " . $c['lessons'] . " lessons, certificate on completion.";
        $id = $this->makeProduct($sku, $name, 'course', $c['price'], $c['compare'], 0, $c['age_min'], $c['age_max'], $c['hsn'], $cats, $shortDesc, $longDesc);
        if (! $id) return;

        $this->db->table('courses')->ignore(true)->insert([
            'product_id'           => $id,
            'instructor_name'      => $c['instructor'],
            'instructor_bio'       => "{$c['instructor']} is a Khoobie-curated educator.",
            'language'             => 'English',
            'level'                => $c['level'],
            'total_minutes'        => $c['minutes'],
            'lessons_count'        => $c['lessons'],
            'what_youll_learn'     => json_encode($c['what_youll_learn']),
            'access_days'          => 365,
            'certificate_available'=> 1,
        ]);
        $courseId = (int) $this->db->table('courses')->where('product_id', $id)->get()->getRow()->id;

        $sortM = 10;
        foreach ($c['modules'] as $m) {
            $this->db->table('course_modules')->insert([
                'course_id'  => $courseId,
                'title'      => $m['title'],
                'description'=> null,
                'sort_order' => $sortM,
            ]);
            $moduleId = (int) $this->db->insertID();
            $sortL = 10;
            foreach ($m['lessons'] as $l) {
                $this->db->table('course_lessons')->insert([
                    'module_id'        => $moduleId,
                    'title'            => $l['title'],
                    'video_url'        => 'https://www.youtube.com/embed/M7lc1UVf-VE', // demo
                    'video_provider'   => 'youtube',
                    'duration_minutes' => $l['minutes'],
                    'is_preview'       => $l['preview'] ?? 0,
                    'sort_order'       => $sortL,
                ]);
                $sortL += 10;
            }
            $sortM += 10;
        }
    }

    protected function createTuition(string $sku, string $name, array $t, array $cats): void
    {
        $shortDesc = "{$t['subject']} · " . implode('/', $t['days']) . ' ' . substr($t['start'], 0, 5) . '–' . substr($t['end'], 0, 5) . " · with {$t['instructor']} · billed {$t['billing']}";
        $id = $this->makeProduct($sku, $name, 'tuition', $t['price'], $t['compare'], 0, $t['age_min'], $t['age_max'], $t['hsn'], $cats, $shortDesc, "Recurring class with the same group every week. Trial class available.");
        if (! $id) return;

        $this->db->table('tuitions')->ignore(true)->insert([
            'product_id'      => $id,
            'subject'         => $t['subject'],
            'grade_level'     => $t['grade'],
            'instructor_name' => $t['instructor'],
            'days_of_week'    => json_encode($t['days']),
            'start_time'      => $t['start'],
            'end_time'        => $t['end'],
            'modality'        => $t['modality'],
            'max_students'    => $t['max_students'],
            'trial_available' => 1,
            'billing_cycle'   => $t['billing'],
            'is_active'       => 1,
        ]);
    }

    protected function createMeetup(string $sku, string $name, array $m, array $cats): void
    {
        $shortDesc = "{$m['location']} · " . date('j M Y', strtotime($m['starts'])) . " · " . ($m['free'] ? 'Free RSVP' : 'Paid event');
        $id = $this->makeProduct($sku, $name, 'meetup', $m['price'], $m['compare'], 0, $m['age_min'], $m['age_max'], $m['hsn'], $cats, $shortDesc, $shortDesc);
        if (! $id) return;

        $this->db->table('meetups')->ignore(true)->insert([
            'product_id'    => $id,
            'location_name' => $m['location'],
            'address'       => $m['address'],
            'city'          => $m['city'],
            'state'         => $m['state'],
            'pincode'       => $m['pincode'],
            'latitude'      => $m['lat'],
            'longitude'     => $m['lng'],
            'maps_url'      => $m['maps'],
            'starts_at'     => $m['starts'],
            'ends_at'       => $m['ends'],
            'capacity'      => $m['capacity'],
            'is_free'       => $m['free'],
            'rsvp_required' => 1,
            'host_name'     => $m['host'],
            'host_phone'    => $m['host_phone'] ?? null,
            'agenda'        => json_encode($m['agenda']),
            'status'        => 'published',
        ]);
    }

    protected function createService(string $sku, string $name, array $s, array $cats): void
    {
        $shortDesc = ucfirst(str_replace('_',' ', $s['kind'])) . " · {$s['duration']} min · {$s['modality']}";
        $id = $this->makeProduct($sku, $name, 'service', $s['price'], $s['compare'], 0, $s['age_min'], $s['age_max'], $s['hsn'], $cats, $shortDesc, "Hands-on 1-on-1 booking with a Khoobie-vetted expert.");
        if (! $id) return;

        $this->db->table('services')->ignore(true)->insert([
            'product_id'      => $id,
            'service_kind'    => $s['kind'],
            'provider_name'   => $s['provider'],
            'duration_minutes'=> $s['duration'],
            'modality'        => $s['modality'],
            'is_active'       => 1,
        ]);

        // Create a few future slots
        if (! empty($s['slots']) && $s['slots'] > 0) {
            $serviceId = (int) $this->db->table('services')->where('product_id', $id)->get()->getRow()->id;
            for ($i = 1; $i <= $s['slots']; $i++) {
                $startTs = strtotime("+{$i} days 18:00");
                $this->db->table('service_slots')->insert([
                    'service_id' => $serviceId,
                    'starts_at'  => date('Y-m-d H:i:s', $startTs),
                    'ends_at'    => date('Y-m-d H:i:s', $startTs + ($s['duration'] * 60)),
                    'is_booked'  => 0,
                ]);
            }
        }
    }

    protected function createMembership(string $sku, string $name, array $m, array $cats): void
    {
        $id = $this->makeProduct($sku, $name, 'membership', $m['price'], $m['compare'], 0, $m['age_min'], $m['age_max'], $m['hsn'], $cats, $m['description'], $m['description']);
        if (! $id) return;

        $this->db->table('memberships')->ignore(true)->insert([
            'product_id'       => $id,
            'tier_name'        => $m['tier'],
            'monthly_price'    => $m['monthly'],
            'annual_price'     => $m['annual'],
            'description'      => $m['description'],
            'perks'            => json_encode($m['perks']),
            'discount_pct'     => $m['discount'],
            'free_shipping'    => $m['free_ship'],
            'early_access'     => $m['early'],
            'free_courses'     => $m['free_courses'],
            'bonus_points_pct' => $m['bonus'],
            'is_active'        => 1,
        ]);
    }
}
