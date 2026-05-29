<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the Khoobie Classes & Coaching marketplace.
 *
 * Creates 4 bucket categories under a new "Classes & Coaching" parent + 18 leaf
 * topics, then generates 12 instructor offerings per topic (~216 listings total)
 * spread across tuitions / courses / meetups / services.
 *
 * Idempotent — each listing has a deterministic SKU (KK-MKT-{topic}-{seq}); reruns
 * skip any that already exist. Safe to invoke alongside the original EcosystemSeeder.
 */
class MarketplaceClassesSeeder extends Seeder
{
    /** Pool of 30 plausible Indian instructor profiles. */
    private array $instructors = [
        ['name' => 'Priya Sharma',      'cred' => 'Bharatiya Kala Kendra alumna',                'years' => 8],
        ['name' => 'Arjun Mehta',       'cred' => 'IIT-B grad, FIDE Candidate Master',           'years' => 6],
        ['name' => 'Riya Kapoor',       'cred' => 'NID-trained visual artist',                   'years' => 7],
        ['name' => 'Vikram Joshi',      'cred' => 'Vishwa Hindu Parishad scholar',               'years' => 12],
        ['name' => 'Ananya Iyer',       'cred' => 'Carnatic vocalist, Music College Madras',     'years' => 10],
        ['name' => 'Rohan Desai',       'cred' => 'Ex-national-level swimmer, NIS coach',        'years' => 9],
        ['name' => 'Meera Nair',        'cred' => 'Kalakshetra alumna, Bharatanatyam',           'years' => 11],
        ['name' => 'Aditya Verma',      'cred' => 'WCA-certified speedcuber, 9.3s avg',          'years' => 5],
        ['name' => 'Sanya Khanna',      'cred' => 'Cambridge CELTA, ESL specialist',             'years' => 6],
        ['name' => 'Kabir Singh',       'cred' => 'Black Belt 3rd Dan Taekwondo',                'years' => 12],
        ['name' => 'Aarav Patil',       'cred' => 'Studio potter, Bangalore Clay Co.',           'years' => 7],
        ['name' => 'Devika Reddy',      'cred' => 'IIM-B PG, Toastmasters DTM',                  'years' => 9],
        ['name' => 'Ishan Bose',        'cred' => 'Visharad in Sitar, Sangeet Bhavan',           'years' => 14],
        ['name' => 'Tara Krishnan',     'cred' => 'Kathak Praveen, Lucknow Gharana',             'years' => 13],
        ['name' => 'Pranav Bhatia',     'cred' => 'Aloha-certified abacus trainer',              'years' => 8],
        ['name' => 'Nisha Pillai',      'cred' => 'Yoga Alliance RYT-500',                       'years' => 10],
        ['name' => 'Karan Malhotra',    'cred' => 'IIT-D, ACM ICPC Regionalist',                 'years' => 4],
        ['name' => 'Aisha Qureshi',     'cred' => 'JNU MA Sanskrit, Vedic chant scholar',        'years' => 9],
        ['name' => 'Siddharth Rao',     'cred' => 'NIS-Patiala karate coach',                    'years' => 11],
        ['name' => 'Rhea Banerjee',     'cred' => 'NIFT alumna, calligraphy & lettering',        'years' => 6],
        ['name' => 'Manish Agarwal',    'cred' => 'CA, founder MathLab Junior',                  'years' => 10],
        ['name' => 'Vandana Shenoy',    'cred' => 'Madhubani national-award lineage',            'years' => 15],
        ['name' => 'Harsh Gupta',       'cred' => 'IIIT-H, Codeforces Master',                   'years' => 5],
        ['name' => 'Bhavna Trivedi',    'cred' => 'Origami Sosaku member, Tokyo',                'years' => 8],
        ['name' => 'Yash Solanki',      'cred' => 'State-level rollerskating champ',             'years' => 7],
        ['name' => 'Lakshmi Menon',     'cred' => 'Kerala Kalamandalam Mohiniyattam',            'years' => 14],
        ['name' => 'Ravi Naidu',        'cred' => 'BCCI Level-2 cricket coach',                  'years' => 13],
        ['name' => 'Sneha Bhonsle',     'cred' => 'Pune Art League watercolour mentor',          'years' => 9],
        ['name' => 'Anand Iyengar',     'cred' => 'Ramamani Iyengar Yoga Institute',             'years' => 16],
        ['name' => 'Pooja Ramesh',      'cred' => 'Mom + maker, 200k Insta followers',           'years' => 5],
    ];

    /**
     * 11 metros × 3-4 localities × 2-3 areas = ~120 hyperlocal slots.
     * Real neighbourhoods + realistic pincodes for credible PDP rendering.
     */
    private array $cityLocalities = [
        'Mumbai' => [
            'state'=>'Maharashtra', 'lat'=>19.0760, 'lng'=>72.8777,
            'localities' => [
                ['name'=>'Bandra',    'pincode'=>'400050', 'areas'=>['West','Linking Road','Pali Hill']],
                ['name'=>'Andheri',   'pincode'=>'400053', 'areas'=>['West','Lokhandwala','Versova']],
                ['name'=>'Powai',     'pincode'=>'400076', 'areas'=>['Hiranandani','IIT Powai','Lake View']],
                ['name'=>'Juhu',      'pincode'=>'400049', 'areas'=>['Tara Road','Beach Road','Vile Parle']],
            ],
        ],
        'Bangalore' => [
            'state'=>'Karnataka', 'lat'=>12.9716, 'lng'=>77.5946,
            'localities' => [
                ['name'=>'Indiranagar','pincode'=>'560038', 'areas'=>['100 Feet Road','Stage 1','Stage 2']],
                ['name'=>'Koramangala','pincode'=>'560034', 'areas'=>['Block 1','Block 4','Block 7']],
                ['name'=>'Whitefield', 'pincode'=>'560066', 'areas'=>['ITPL','Brookefield','Hagadur']],
                ['name'=>'Jayanagar',  'pincode'=>'560011', 'areas'=>['4th Block','9th Block','11th Main']],
            ],
        ],
        'Pune' => [
            'state'=>'Maharashtra', 'lat'=>18.5204, 'lng'=>73.8567,
            'localities' => [
                ['name'=>'Koregaon Park', 'pincode'=>'411001', 'areas'=>['Lane 1','Lane 5','North Main Road']],
                ['name'=>'Baner',         'pincode'=>'411045', 'areas'=>['Pashan Link Road','Sus Road']],
                ['name'=>'Hinjewadi',     'pincode'=>'411057', 'areas'=>['Phase 1','Phase 2','Phase 3']],
                ['name'=>'Aundh',         'pincode'=>'411007', 'areas'=>['ITI Road','Westend Mall Area']],
            ],
        ],
        'Delhi' => [
            'state'=>'Delhi', 'lat'=>28.6139, 'lng'=>77.2090,
            'localities' => [
                ['name'=>'Rohini',      'pincode'=>'110085', 'areas'=>['Sector 7','Sector 13','Sector 24']],
                ['name'=>'Saket',       'pincode'=>'110017', 'areas'=>['B Block','D Block','Press Enclave']],
                ['name'=>'Vasant Kunj', 'pincode'=>'110070', 'areas'=>['Sector A','Sector D','Pocket 1']],
                ['name'=>'Dwarka',      'pincode'=>'110075', 'areas'=>['Sector 6','Sector 12','Sector 19']],
            ],
        ],
        'Noida' => [
            'state'=>'Uttar Pradesh', 'lat'=>28.5355, 'lng'=>77.3910,
            'localities' => [
                ['name'=>'Sector 62',  'pincode'=>'201309', 'areas'=>['C Block','D Block','Block A']],
                ['name'=>'Sector 18',  'pincode'=>'201301', 'areas'=>['Atta Market','GIP Mall Area']],
                ['name'=>'Sector 110', 'pincode'=>'201304', 'areas'=>['Hyde Park','ATS','Mahagun']],
            ],
        ],
        'Gurgaon' => [
            'state'=>'Haryana', 'lat'=>28.4595, 'lng'=>77.0266,
            'localities' => [
                ['name'=>'DLF Phase 1','pincode'=>'122002', 'areas'=>['Galleria Market','A Block']],
                ['name'=>'Sector 56',  'pincode'=>'122011', 'areas'=>['Wazirabad Road','Park View']],
                ['name'=>'Sohna Road', 'pincode'=>'122018', 'areas'=>['Sector 49','Omaxe City']],
                ['name'=>'Cyber City', 'pincode'=>'122002', 'areas'=>['Tower A','DLF Phase 2']],
            ],
        ],
        'Hyderabad' => [
            'state'=>'Telangana', 'lat'=>17.3850, 'lng'=>78.4867,
            'localities' => [
                ['name'=>'Banjara Hills','pincode'=>'500034', 'areas'=>['Road No. 1','Road No. 12']],
                ['name'=>'Gachibowli',  'pincode'=>'500032', 'areas'=>['Financial District','IIIT-H Area']],
                ['name'=>'Madhapur',    'pincode'=>'500081', 'areas'=>['HITEC City','Image Gardens']],
                ['name'=>'Kondapur',    'pincode'=>'500084', 'areas'=>['Botanical Garden Road','Whitefields']],
            ],
        ],
        'Chennai' => [
            'state'=>'Tamil Nadu', 'lat'=>13.0827, 'lng'=>80.2707,
            'localities' => [
                ['name'=>'T. Nagar',    'pincode'=>'600017', 'areas'=>['Pondy Bazaar','Ranganathan Street']],
                ['name'=>'Adyar',       'pincode'=>'600020', 'areas'=>['Gandhi Nagar','Indira Nagar']],
                ['name'=>'Anna Nagar',  'pincode'=>'600040', 'areas'=>['West','East','2nd Avenue']],
                ['name'=>'Velachery',   'pincode'=>'600042', 'areas'=>['Phoenix Mall Area','Pasumai Estate']],
            ],
        ],
        'Kolkata' => [
            'state'=>'West Bengal', 'lat'=>22.5726, 'lng'=>88.3639,
            'localities' => [
                ['name'=>'Salt Lake',  'pincode'=>'700091', 'areas'=>['Sector V','AA Block','EE Block']],
                ['name'=>'Park Street','pincode'=>'700016', 'areas'=>['Camac Street','Russell Street']],
                ['name'=>'Ballygunge', 'pincode'=>'700019', 'areas'=>['Circular Road','Lake Gardens']],
                ['name'=>'New Town',   'pincode'=>'700156', 'areas'=>['Action Area I','Action Area II']],
            ],
        ],
        'Ahmedabad' => [
            'state'=>'Gujarat', 'lat'=>23.0225, 'lng'=>72.5714,
            'localities' => [
                ['name'=>'Satellite', 'pincode'=>'380015', 'areas'=>['Prerna Tirth','Iskcon Crossroads']],
                ['name'=>'Bodakdev',  'pincode'=>'380054', 'areas'=>['Sindhu Bhavan Road','Mansi Circle']],
                ['name'=>'Vastrapur', 'pincode'=>'380015', 'areas'=>['Mansi Circle','AES Cross Roads']],
                ['name'=>'SG Highway','pincode'=>'380060', 'areas'=>['Shilaj','Ognaj']],
            ],
        ],
        'Jaipur' => [
            'state'=>'Rajasthan', 'lat'=>26.9124, 'lng'=>75.7873,
            'localities' => [
                ['name'=>'Malviya Nagar', 'pincode'=>'302017', 'areas'=>['Sector 11','Sector 12']],
                ['name'=>'Vaishali Nagar','pincode'=>'302021', 'areas'=>['Amrapali Circle','JDA Park']],
                ['name'=>'C-Scheme',      'pincode'=>'302001', 'areas'=>['Civil Lines','MI Road']],
                ['name'=>'Mansarovar',    'pincode'=>'302020', 'areas'=>['Sector 4','Madhyam Marg']],
            ],
        ],
    ];

    /**
     * Flat list — used by the iteration in seedTopic() so each meetup gets a
     * specific (city, locality, area, pincode) combo deterministically.
     */
    private array $cities = [];

    /** Recurring class time slots. */
    private array $schedules = [
        ['days'=>['Mon','Wed','Fri'], 'start'=>'17:00:00', 'end'=>'18:00:00'],
        ['days'=>['Tue','Thu'],       'start'=>'18:00:00', 'end'=>'19:00:00'],
        ['days'=>['Sat','Sun'],       'start'=>'10:00:00', 'end'=>'11:00:00'],
        ['days'=>['Mon','Thu'],       'start'=>'16:00:00', 'end'=>'17:00:00'],
        ['days'=>['Sat'],             'start'=>'09:00:00', 'end'=>'10:30:00'],
        ['days'=>['Sun'],             'start'=>'11:00:00', 'end'=>'12:30:00'],
    ];

    public function run()
    {
        // Flatten cityLocalities into the cities iteration pool
        $this->cities = $this->expandCities();

        $catIds = $this->ensureCategories();
        foreach ($this->topicSpecs() as $key => $topic) {
            $this->seedTopic($key, $topic, $catIds);
        }

        // Backfill any meetups (existing OR new) whose locality is NULL
        $backfilled = $this->backfillMeetupLocalities();

        // Soft refresh: show summary
        $db = $this->db;
        $count = (int) $db->table('products')->where('sku LIKE', 'KK-MKT-%')->countAllResults();
        $tuit  = (int) $db->table('products')->where('sku LIKE', 'KK-MKT-%')->where('type','tuition')->countAllResults();
        $crs   = (int) $db->table('products')->where('sku LIKE', 'KK-MKT-%')->where('type','course')->countAllResults();
        $mtp   = (int) $db->table('products')->where('sku LIKE', 'KK-MKT-%')->where('type','meetup')->countAllResults();
        $svc   = (int) $db->table('products')->where('sku LIKE', 'KK-MKT-%')->where('type','service')->countAllResults();
        echo "Marketplace seeded: total {$count} listings ({$tuit} tuitions + {$crs} courses + {$mtp} meetups + {$svc} services)\n";
        echo "Hyperlocal: backfilled {$backfilled} meetup row(s) with locality + area.\n";
    }

    /** Flatten cityLocalities → list of (city, state, lat, lng, locality, area, pincode) rows. */
    private function expandCities(): array
    {
        $out = [];
        foreach ($this->cityLocalities as $city => $cfg) {
            foreach ($cfg['localities'] as $loc) {
                foreach ($loc['areas'] as $area) {
                    $out[] = [
                        'city'     => $city,
                        'state'    => $cfg['state'],
                        'lat'      => $cfg['lat'],
                        'lng'      => $cfg['lng'],
                        'locality' => $loc['name'],
                        'area'     => $area,
                        'pincode'  => $loc['pincode'],
                    ];
                }
            }
        }
        return $out;
    }

    /**
     * Backfill the meetups table so any row with NULL locality gets one,
     * deterministically picked from the pool based on its city + product id.
     */
    private function backfillMeetupLocalities(): int
    {
        $rows = $this->db->table('meetups')
            ->select('id, city, product_id, location_name')
            ->where('locality', null)
            ->get()->getResultArray();
        if (empty($rows)) return 0;

        // Group cities → eligible locality+area combos
        $byCity = [];
        foreach ($this->cities as $c) $byCity[$c['city']][] = $c;

        // Stable per-city counter so we round-robin through locality/area combos
        // instead of all rows hashing to the same modular slot.
        $cityCursor = [];

        $updated = 0;
        foreach ($rows as $r) {
            $pool = $byCity[$r['city']] ?? null;
            if (! $pool) continue;

            // Round-robin pick (deterministic order: product_id ascending → slot 0,1,2,…)
            $idx = $cityCursor[$r['city']] ?? 0;
            $pick = $pool[$idx % count($pool)];
            $cityCursor[$r['city']] = $idx + 1;

            // Update location_name so the PDP shows the locality, not just city
            $newLocName = sprintf('Khoobie Studio Partner — %s, %s', $pick['locality'], $pick['city']);

            $this->db->table('meetups')->where('id', $r['id'])->update([
                'locality'      => $pick['locality'],
                'area'          => $pick['area'],
                'pincode'       => $pick['pincode'],
                'address'       => sprintf('%s, %s, %s — %s', $pick['area'], $pick['locality'], $pick['city'], $pick['pincode']),
                'location_name' => $newLocName,
                'maps_url'      => sprintf("https://www.google.com/maps/search/%s+%s+%s",
                    rawurlencode($pick['area']), rawurlencode($pick['locality']), rawurlencode($pick['city'])
                ),
            ]);

            // Also refresh the product short_desc + name to surface locality in search results / cards.
            $product = $this->db->table('products')->where('id', $r['product_id'])->get()->getRow();
            if ($product) {
                $newShortDesc = preg_replace(
                    '/Live in-person · [^·]+·/',
                    sprintf('Live in-person · %s › %s › %s ·', $pick['city'], $pick['locality'], $pick['area']),
                    (string) $product->short_desc,
                    1
                );
                // If the product name has " — {city} · " replace with " — {locality}, {city} · "
                $newName = preg_replace(
                    '/ — ' . preg_quote($pick['city'], '/') . ' ·/',
                    " — {$pick['locality']}, {$pick['city']} ·",
                    (string) $product->name,
                    1
                );
                $this->db->table('products')->where('id', $r['product_id'])->update([
                    'short_desc' => $newShortDesc,
                    'name'       => $newName,
                ]);
            }
            $updated++;
        }
        return $updated;
    }

    // ====================================================================
    // CATEGORY TREE
    // ====================================================================
    private function ensureCategories(): array
    {
        $tree = [
            'classes' => ['parent'=>null,'name'=>'Classes & Coaching','icon'=>'🎓','order'=>50],
            'creative-classes'   => ['parent'=>'classes','name'=>'Creative & Crafts',    'icon'=>'🎨','order'=>10],
            'mindsport-classes'  => ['parent'=>'classes','name'=>'Mind Sports',          'icon'=>'♟️','order'=>20],
            'activity-classes'   => ['parent'=>'classes','name'=>'Activity & Confidence','icon'=>'🎤','order'=>30],
            'local-meetups'      => ['parent'=>'classes','name'=>'Local Meetups',        'icon'=>'📍','order'=>40],
        ];
        $ids = [];
        foreach ($tree as $slug => $c) {
            $existing = $this->db->table('categories')->where('slug', $slug)->get()->getRow();
            if ($existing) { $ids[$slug] = (int) $existing->id; continue; }
            $parentId = $c['parent'] ? ($ids[$c['parent']] ?? null) : null;
            $this->db->table('categories')->insert([
                'parent_id' => $parentId,
                'slug'      => $slug,
                'name'      => $c['name'],
                'icon'      => $c['icon'],
                'sort_order'=> $c['order'],
                'is_active' => 1,
            ]);
            $ids[$slug] = (int) $this->db->insertID();
        }
        return $ids;
    }

    // ====================================================================
    // TOPIC SPECS — the 18 topics, each with mix of product types
    // ====================================================================
    private function topicSpecs(): array
    {
        return [
            // ────────── BUCKET A — CREATIVE & CRAFTS ──────────
            'pottery' => [
                'topic'    => 'Pottery & Clay Modelling',
                'bucket'   => 'creative-classes',
                'mix'      => ['tuition'=>6,'meetup'=>3,'service'=>2,'course'=>1],
                'priceTuition' => [180000, 220000, 280000, 350000],  // ₹1800-3500/mo
                'priceCourse'  => [129900],                            // ₹1299 one-off
                'priceMeetup'  => [89900, 149900, 199900],             // ₹899-1999 weekend
                'priceService' => [199900, 299900],                    // ₹1999-2999 per session
                'ageGroups'    => [[5,8],[7,10],[9,13],[10,15]],
                'levels'       => ['beginner','intermediate','advanced'],
                'curriculum'   => [
                    'Pinch-pot & coil basics — your first bowl',
                    'Slab construction — making a planter',
                    'Wheel introduction (online: hand-building substitute)',
                    'Centering & throwing your first cylinder',
                    'Trimming, foot rings & functional ware',
                    'Surface decoration — sgraffito, slip, carving',
                    'Glaze chemistry for kids — colour theory',
                    'Glaze firing & finishing — kiln demo',
                    'Studio safety + tool care',
                    'Final project: design & make a 4-piece dinner set',
                ],
                'deliverables' => [
                    '4 finished, fired & glazed pieces you take home',
                    'Reusable potter\'s tool kit (digital orders shipped)',
                    'Lifetime access to recorded class library',
                    'WhatsApp parent group for between-class support',
                    'Certificate of completion + showcase reel',
                ],
                'icon' => '🏺',
            ],

            'folk-art' => [
                'topic'    => 'Madhubani, Warli & Indian Folk Art',
                'bucket'   => 'creative-classes',
                'mix'      => ['tuition'=>6,'course'=>4,'meetup'=>2],
                'priceTuition'=> [150000, 200000, 250000],
                'priceCourse' => [99900, 149900, 199900],
                'priceMeetup' => [99900, 149900],
                'ageGroups'   => [[7,10],[10,14],[12,16]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Origin stories — Madhubani, Warli, Gond, Pattachitra',
                    'Madhubani: the iconic fish & peacock motifs',
                    'Warli: stick-figure storytelling on canvas',
                    'Colour mixing with natural pigments',
                    'Borders, geometric patterns & symmetry',
                    'Gond: dot-and-line technique',
                    'Pattachitra: religious narrative scrolls',
                    'Composition: telling a story in a single frame',
                    'Final project: a 16×20" canvas of your choice',
                    'Showcase: virtual gallery walk-through',
                ],
                'deliverables' => [
                    '6 finished folk-art pieces ready to frame',
                    'Khoobie folk-art starter kit (paints, brushes, canvases)',
                    'Pattern reference library (50+ motifs)',
                    'Final showcase invitation + Insta feature',
                    'Certificate signed by national-award lineage instructor',
                ],
                'icon' => '🎨',
            ],

            'mandala' => [
                'topic'    => 'Mandala Art & Mindful Doodling',
                'bucket'   => 'creative-classes',
                'mix'      => ['course'=>8,'tuition'=>4],
                'priceTuition'=> [80000, 120000],
                'priceCourse' => [49900, 79900, 99900, 149900],
                'ageGroups'   => [[8,12],[10,14],[12,16]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Why mandalas? Symmetry, meditation & focus',
                    'Drawing the grid: divisions & layers',
                    'Floral mandalas — petals & repetition',
                    'Geometric mandalas — angles & precision',
                    'Mythology-inspired mandalas',
                    'Nature mandalas — leaves, shells, waves',
                    'Colour theory for mandalas',
                    'Free-form mandala — your unique voice',
                    'Sealing, framing & sharing',
                    'Bonus: zentangle patterns',
                ],
                'deliverables' => [
                    '8+ finished mandala artworks',
                    'PDF printable mandala templates (50+)',
                    'Brush-pen recommendations list',
                    'Lifetime course access',
                    'Certificate + showcase wall',
                ],
                'icon' => '🌀',
            ],

            'origami' => [
                'topic'    => 'Origami, Quilling & Paper Craft',
                'bucket'   => 'creative-classes',
                'mix'      => ['tuition'=>6,'course'=>4,'meetup'=>2],
                'priceTuition'=> [80000, 120000, 180000],
                'priceCourse' => [49900, 79900, 99900],
                'priceMeetup' => [49900, 99900],
                'ageGroups'   => [[5,8],[7,10],[9,13]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Folding the basics: valley & mountain folds',
                    'Classic origami — crane, frog, lily',
                    'Modular origami — kusudama balls',
                    'Quilling 101: rolling & shaping strips',
                    'Quilled cards & frames',
                    'Pop-up cards — engineering with paper',
                    'Paper-mâché bowls & masks',
                    'Scrapbook layouts & album making',
                    '3D paper sculptures',
                    'Final showcase: a complete handmade gift hamper',
                ],
                'deliverables' => [
                    'Paper craft starter kit (papers, quilling tool, glue)',
                    '20+ projects to take home',
                    'Printable templates library',
                    'Lifetime course library access',
                    'Certificate + photo book of your work',
                ],
                'icon' => '📄',
            ],

            'calligraphy' => [
                'topic'    => 'Calligraphy & Hand Lettering',
                'bucket'   => 'creative-classes',
                'mix'      => ['tuition'=>8,'course'=>4],
                'priceTuition'=> [150000, 200000, 250000, 300000],
                'priceCourse' => [99900, 149900, 199900],
                'ageGroups'   => [[10,14],[12,16],[13,17]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Tools 101 — pens, nibs, ink, paper',
                    'Posture, grip & warm-up drills',
                    'Lowercase alphabet — foundational strokes',
                    'Uppercase alphabet — flourishes',
                    'Italic & copperplate scripts',
                    'Modern brush lettering',
                    'Devanagari calligraphy',
                    'Composing words & phrases',
                    'Designing greeting cards',
                    'Final: hand-lettered poster ready to frame',
                ],
                'deliverables' => [
                    'Calligraphy kit (pens, nibs, ink, practice pads)',
                    'Printable practice sheets (200+)',
                    'Reference style guide PDF',
                    'Personalised feedback on 5 submissions',
                    'Certificate + portfolio piece',
                ],
                'icon' => '✒️',
            ],

            // ────────── BUCKET B — MIND SPORTS ──────────
            'chess' => [
                'topic'    => 'Chess Coaching',
                'bucket'   => 'mindsport-classes',
                'mix'      => ['tuition'=>8,'service'=>2,'course'=>2],
                'priceTuition'=> [200000, 280000, 350000, 450000],
                'priceCourse' => [149900, 249900],
                'priceService'=> [199900, 299900, 499900],
                'ageGroups'   => [[5,8],[7,11],[10,14],[13,17]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Pieces, board setup & basic moves',
                    'Pawn play & basic openings',
                    'Tactics — pins, forks, skewers',
                    'Checkmate patterns (back-rank, smothered, scholar)',
                    'Endgame fundamentals — king & pawn',
                    'Opening principles & repertoire building',
                    'Middlegame strategy & positional play',
                    'Famous games — Anand, Carlsen, Gukesh deep-dives',
                    'Tournament etiquette & clock management',
                    'FIDE rating prep & rapid/blitz formats',
                ],
                'deliverables' => [
                    'Weekly puzzle sets via Lichess study',
                    'Recorded analysis of your tournament games',
                    'Opening repertoire PDF tailored to your style',
                    'Quarterly internal-rating tournaments',
                    'Direct path to FIDE-rated events with the coach',
                ],
                'icon' => '♟️',
            ],

            'cubing' => [
                'topic'    => 'Speed Cubing (Rubik\'s Cube)',
                'bucket'   => 'mindsport-classes',
                'mix'      => ['tuition'=>8,'course'=>4],
                'priceTuition'=> [100000, 150000, 200000, 250000],
                'priceCourse' => [49900, 79900, 99900],
                'ageGroups'   => [[7,11],[10,14],[13,17]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Cube anatomy & notation (R, U, F\')',
                    'Beginner\'s method — solving in 7 steps',
                    'Cross & F2L (first two layers)',
                    'OLL (orient last layer) — 7 algorithms',
                    'PLL (permute last layer) — 7 algorithms',
                    'CFOP method — full Fridrich',
                    'Lookahead training & finger tricks',
                    'Sub-30s, sub-20s, sub-15s drills',
                    'WCA competition format & official scrambles',
                    'Beyond 3×3 — Pyraminx, Skewb, 4×4 intro',
                ],
                'deliverables' => [
                    'Beginner-friendly speed cube (shipped)',
                    'Personalised algorithm sheet',
                    'Lookahead drill videos library',
                    'Monthly mock WCA competitions on Zoom',
                    'Sub-X badge progression (sub-60, sub-30, sub-20)',
                ],
                'icon' => '🧊',
            ],

            'abacus' => [
                'topic'    => 'Abacus & Mental Math',
                'bucket'   => 'mindsport-classes',
                'mix'      => ['tuition'=>10,'course'=>2],
                'priceTuition'=> [150000, 200000, 250000, 300000],
                'priceCourse' => [99900, 149900],
                'ageGroups'   => [[5,8],[6,10],[8,12],[10,13]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Abacus 101 — parts, beads, value placement',
                    'Adding single digits with friends/complements',
                    'Visualising the abacus (mental abacus)',
                    'Subtraction with borrowing',
                    'Multiplication tables on the abacus',
                    'Multi-digit multiplication shortcuts',
                    'Division strategies',
                    'Decimals & fractions',
                    'Speed drills — 30 sums in 60 seconds',
                    'Aloha/UCMAS competition prep',
                ],
                'deliverables' => [
                    'Wooden abacus shipped to your door',
                    '8 leveled workbooks',
                    'Weekly speed-test certificates',
                    'Annual mental-math championship eligibility',
                    'Parent dashboard with weekly progress reports',
                ],
                'icon' => '🧮',
            ],

            'vedic-maths' => [
                'topic'    => 'Vedic Mathematics',
                'bucket'   => 'mindsport-classes',
                'mix'      => ['tuition'=>8,'course'=>4],
                'priceTuition'=> [120000, 180000, 240000],
                'priceCourse' => [99900, 149900, 199900],
                'ageGroups'   => [[8,12],[10,14],[12,16]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'The 16 sutras — the building blocks',
                    'Ekadhikena Purvena — adding & checking',
                    'Nikhilam — multiplication near a base',
                    'Urdhva-Tiryagbhyam — vertical & crosswise',
                    'Squaring numbers ending in 5',
                    'Cubes & cube roots',
                    'Division by 9, 11, near-base divisors',
                    'Quick fractions & decimals',
                    'Mental algebra & equations',
                    'Speed: solve in seconds vs. school methods',
                ],
                'deliverables' => [
                    'Full Vedic Maths workbook (printed + PDF)',
                    'Weekly assessment with timed-test certificates',
                    'Comparison chart vs. school methods',
                    'Olympiad-prep bonus module',
                    'Certificate of mastery',
                ],
                'icon' => '🔢',
            ],

            // ────────── BUCKET C — ACTIVITY & CONFIDENCE ──────────
            'public-speaking' => [
                'topic'    => 'Public Speaking & Storytelling',
                'bucket'   => 'activity-classes',
                'mix'      => ['tuition'=>8,'service'=>2,'course'=>2],
                'priceTuition'=> [200000, 280000, 350000],
                'priceCourse' => [149900, 199900],
                'priceService'=> [199900, 299900],
                'ageGroups'   => [[7,11],[10,14],[13,17]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Stage fear & breathing exercises',
                    'Voice modulation & projection',
                    'Storytelling structure — beginning, middle, end',
                    'Impromptu speaking — table topics',
                    'Body language & eye contact',
                    'Persuasive speaking & debate basics',
                    'Speech writing — hook, body, call to action',
                    'Using props, slides & visuals',
                    'Recording yourself — self-critique',
                    'Final showcase: 5-minute TED-style talk',
                ],
                'deliverables' => [
                    'Recorded showcase video for portfolio',
                    'Personalised feedback on every speech',
                    'Speech-script templates library',
                    'Toastmasters Youth Leadership entry path',
                    'Certificate + parent showcase event invitation',
                ],
                'icon' => '🎤',
            ],

            'yoga' => [
                'topic'    => 'Yoga & Mindfulness for Kids',
                'bucket'   => 'activity-classes',
                'mix'      => ['tuition'=>8,'service'=>2,'course'=>2],
                'priceTuition'=> [80000, 120000, 180000],
                'priceCourse' => [49900, 99900],
                'priceService'=> [99900, 149900],
                'ageGroups'   => [[5,8],[7,11],[10,14]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Warm-ups — joints, surya namaskar',
                    'Standing asanas — strength & balance',
                    'Seated asanas — flexibility & posture',
                    'Backbends & forward folds',
                    'Inversions for kids — dolphin, plough (supported)',
                    'Pranayama — anulom-vilom, bhramari, ujjayi',
                    'Meditation & yoga nidra for kids',
                    'Yoga for focus & exam stress',
                    'Yoga for posture & screen-time fatigue',
                    'Final flow: full 30-min self-led practice',
                ],
                'deliverables' => [
                    'Kid-sized yoga mat (shipped)',
                    'Pose flash-cards for at-home practice',
                    'Bedtime yoga audio (10 tracks)',
                    'Monthly mindfulness journal',
                    'Certificate from Yoga Alliance RYT-certified coach',
                ],
                'icon' => '🧘',
            ],

            'spoken-english' => [
                'topic'    => 'Spoken English & Phonics',
                'bucket'   => 'activity-classes',
                'mix'      => ['tuition'=>8,'service'=>2,'course'=>2],
                'priceTuition'=> [100000, 150000, 200000, 250000],
                'priceCourse' => [99900, 149900],
                'priceService'=> [199900, 299900],
                'ageGroups'   => [[5,8],[7,10],[9,13],[12,16]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Phonics — Jolly Phonics 7 groups',
                    'Sight words & sentence starters',
                    'Conversation basics — greetings, intros',
                    'Pronunciation drills (Indian accent neutralisation)',
                    'Vocabulary boosters — daily 10 words',
                    'Tense mastery — past, present, future',
                    'Reading aloud — fluency building',
                    'Story narration & role-play',
                    'Email & letter writing',
                    'Public speaking finale',
                ],
                'deliverables' => [
                    'Phonics flashcards (printed + digital)',
                    'Weekly book recommendations',
                    'Recorded pronunciation feedback',
                    'Quarterly Cambridge YLE practice tests',
                    'Certificate + Cambridge prep pathway',
                ],
                'icon' => '🗣️',
            ],

            'classical-vocal' => [
                'topic'    => 'Indian Classical Vocal (Hindustani + Carnatic)',
                'bucket'   => 'activity-classes',
                'mix'      => ['tuition'=>8,'service'=>2,'course'=>2],
                'priceTuition'=> [150000, 200000, 280000, 350000],
                'priceCourse' => [99900, 149900],
                'priceService'=> [199900, 299900],
                'ageGroups'   => [[6,10],[9,13],[12,17]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Sa Re Ga Ma — Swara recognition',
                    'Voice culture — pitch, breath, tone',
                    'Alankars (Hindustani) / Varisai (Carnatic)',
                    'Raag Yaman / Mayamalavagowla — first raag',
                    'Taal — teentaal, ektaal / adi, rupakam',
                    'Bhajans, kritis & devotional pieces',
                    'Bandish / kriti composition basics',
                    'Improvisation — taan / kalpana swara',
                    'Stage practice & sound check',
                    'Final concert: 30-min live performance',
                ],
                'deliverables' => [
                    'Tanpura app + sruti box recommendations',
                    'Personalised raag practice tracks',
                    'Notation books (printed + PDF)',
                    'Quarterly Khoobie student concerts (recorded)',
                    'Visharad / Junior grade exam guidance',
                ],
                'icon' => '🎵',
            ],

            'bharatanatyam' => [
                'topic'    => 'Bharatanatyam & Kathak',
                'bucket'   => 'activity-classes',
                'mix'      => ['tuition'=>8,'service'=>2,'meetup'=>2],
                'priceTuition'=> [180000, 220000, 300000, 380000],
                'priceMeetup' => [149900, 249900],
                'priceService'=> [299900, 449900],
                'ageGroups'   => [[5,9],[8,13],[12,17]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Adavus (Bharatanatyam) / Tatkar (Kathak) — basic steps',
                    'Hasta mudras — hand gestures & meanings',
                    'Posture, balance & footwork drills',
                    'Tala patterns — adi, mishra, chautal',
                    'First items — Alaripu / Vandana',
                    'Abhinaya — facial expression & narrative',
                    'Jathiswaram / Ghat-bhav',
                    'Varnam / Thumri excerpts',
                    'Stage makeup, costume & jewelry',
                    'Arangetram / debut preparation',
                ],
                'deliverables' => [
                    'Dance bell (ghungroo) included with annual fee',
                    'Recorded class library for daily home practice',
                    'Annual student showcase at partner auditorium',
                    'Costume & makeup workshop included',
                    'Pathway to Junior / Senior grade exams',
                ],
                'icon' => '💃',
            ],

            // ────────── BUCKET D — HYPERLOCAL OFFLINE MEETUPS ──────────
            'swimming' => [
                'topic'    => 'Swimming — Local Coaching Batches',
                'bucket'   => 'local-meetups',
                'mix'      => ['meetup'=>12],
                'priceMeetup' => [299900, 399900, 499900, 599900],
                'ageGroups'   => [[5,8],[7,11],[10,14]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Water familiarisation & floating',
                    'Freestyle — kick, pull, breathing',
                    'Backstroke fundamentals',
                    'Breaststroke — frog kick & glide',
                    'Butterfly — undulation & dolphin kick',
                    'Diving & turns',
                    'Endurance training — 200m, 400m',
                    'Pool safety & rescue basics',
                    'Stroke-correction clinic',
                    'Inter-batch friendly meet',
                ],
                'deliverables' => [
                    'Pool access for the full batch (3 months)',
                    'Goggles + cap kit on enrolment',
                    'Stroke-progress badges (1-5)',
                    'Photo & video of each batch graduation',
                    'NIS-certified coach with safety lifeguard always on duty',
                ],
                'icon' => '🏊',
                'forceOffline' => true,
            ],

            'skating' => [
                'topic'    => 'Skating — Inline & Quad',
                'bucket'   => 'local-meetups',
                'mix'      => ['meetup'=>12],
                'priceMeetup' => [199900, 299900, 399900],
                'ageGroups'   => [[5,8],[7,11],[10,14]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Gear up — skates, helmet, knee/elbow pads',
                    'Standing & first glides',
                    'Stopping safely — T-stop, plough',
                    'Turning — crossovers & swizzles',
                    'Backward skating',
                    'Slalom & cone drills',
                    'Speed skating intro',
                    'Artistic skating moves',
                    'Hockey skating fundamentals',
                    'Inter-batch race day',
                ],
                'deliverables' => [
                    'Rental skates included for first month',
                    'Helmet & full protective gear set',
                    'Level certificates 1–5',
                    'Inter-school competition entry guidance',
                    'Coach is a state-level champion',
                ],
                'icon' => '🛼',
                'forceOffline' => true,
            ],

            'karate' => [
                'topic'    => 'Karate & Taekwondo — Self-Defence',
                'bucket'   => 'local-meetups',
                'mix'      => ['meetup'=>12],
                'priceMeetup' => [199900, 299900, 399900, 499900],
                'ageGroups'   => [[5,8],[7,11],[10,14],[13,17]],
                'levels'      => ['beginner','intermediate','advanced'],
                'curriculum'  => [
                    'Dojo etiquette & warm-up routine',
                    'Stances — zenkutsu, kiba, kokutsu dachi',
                    'Punches — choku, gyaku, jab/cross',
                    'Blocks — age, gedan, soto, uchi uke',
                    'Kicks — mae geri, mawashi, yoko geri',
                    'Kata — Heian Shodan',
                    'Kumite — partner sparring drills',
                    'Self-defence scenarios (girls\' module)',
                    'Belt promotion preparation',
                    'In-house belt grading day',
                ],
                'deliverables' => [
                    'Gi (uniform) issued at white-belt level',
                    'Belt progression — white to coloured',
                    'Federation-recognised belt grading',
                    'Self-defence workshop for parents (one combined session)',
                    'Black-belt 3rd Dan certified coach',
                ],
                'icon' => '🥋',
                'forceOffline' => true,
            ],

            'pottery-weekend' => [
                'topic'    => 'Pottery Weekend Workshops — Drop-in',
                'bucket'   => 'local-meetups',
                'mix'      => ['meetup'=>12],
                'priceMeetup' => [149900, 199900, 249900, 299900],
                'ageGroups'   => [[7,12],[10,15],[12,17]],
                'levels'      => ['beginner'],
                'curriculum'  => [
                    'Studio orientation & clay 101',
                    'Hand-building: pinch pot',
                    'Hand-building: coil vase',
                    'Wheel demonstration & first throw',
                    'Trimming & finishing',
                    'Surface decoration',
                    'Glaze selection',
                    'Studio firing process explained',
                    'Pickup of fired piece after 7 days',
                    'Group photo & social share',
                ],
                'deliverables' => [
                    '1 finished, glazed, kiln-fired piece per child',
                    'Apron & tools provided',
                    'Snacks + chai for parents',
                    '20% off shop voucher (Khoobie clay kits)',
                    'Studio tour + Q&A with the resident potter',
                ],
                'icon' => '🎨',
                'forceOffline' => true,
            ],
        ];
    }

    // ====================================================================
    // PER-TOPIC SEEDER
    // ====================================================================
    private function seedTopic(string $key, array $t, array $catIds): void
    {
        $bucketId = $catIds[$t['bucket']] ?? null;
        $rootId   = $catIds['classes'] ?? null;
        $cats     = array_values(array_filter([$rootId, $bucketId]));

        $seq = 1;
        foreach ($t['mix'] as $type => $count) {
            for ($i = 0; $i < $count; $i++, $seq++) {
                $sku = sprintf('KK-MKT-%s-%02d', strtoupper($key), $seq);
                $instructor = $this->instructors[($seq * 7) % count($this->instructors)];
                $ageGroup   = $t['ageGroups'][$seq % count($t['ageGroups'])];
                $level      = ($t['levels'] ?? ['beginner'])[$seq % count($t['levels'] ?? ['beginner'])];
                $city       = $this->cities[$seq % count($this->cities)];

                switch ($type) {
                    case 'tuition':  $this->mkTuition ($sku, $t, $instructor, $ageGroup, $level, $cats, $seq); break;
                    case 'course':   $this->mkCourse  ($sku, $t, $instructor, $ageGroup, $level, $cats, $seq); break;
                    case 'meetup':   $this->mkMeetup  ($sku, $t, $instructor, $ageGroup, $level, $city, $cats, $seq); break;
                    case 'service':  $this->mkService ($sku, $t, $instructor, $ageGroup, $level, $cats, $seq, $key); break;
                }
            }
        }
    }

    // ====================================================================
    // TYPE-SPECIFIC CREATORS
    // ====================================================================
    private function mkTuition(string $sku, array $t, array $ins, array $age, string $level, array $cats, int $seq): void
    {
        $sched = $this->schedules[$seq % count($this->schedules)];
        $price = $t['priceTuition'][$seq % count($t['priceTuition'])];
        $modality = ! empty($t['forceOffline']) ? 'offline' : (($seq % 4 === 0) ? 'hybrid' : 'online');

        $name = sprintf('%s — %s · with %s', $t['topic'], ucfirst($level), $ins['name']);
        $shortDesc = sprintf('Weekly live classes · %s %s–%s · Ages %d–%d · taught by %s (%s)',
            implode('/', $sched['days']),
            substr($sched['start'],0,5),
            substr($sched['end'],0,5),
            $age[0], $age[1],
            $ins['name'], $ins['cred']
        );
        $longDesc = $this->longDescTuition($t, $ins, $age, $level, $sched, $modality);

        $id = $this->makeProduct($sku, $name, 'tuition', $price, (int) round($price * 1.4), $age[0], $age[1], $cats, $shortDesc, $longDesc);
        if (! $id) return;

        $this->db->table('tuitions')->ignore(true)->insert([
            'product_id'      => $id,
            'subject'         => $t['topic'],
            'grade_level'     => "Ages {$age[0]}–{$age[1]}",
            'instructor_name' => $ins['name'],
            'days_of_week'    => json_encode($sched['days']),
            'start_time'      => $sched['start'],
            'end_time'        => $sched['end'],
            'modality'        => $modality,
            'max_students'    => $modality === 'online' ? 12 : 8,
            'trial_available' => 1,
            'billing_cycle'   => 'monthly',
            'is_active'       => 1,
        ]);
    }

    private function mkCourse(string $sku, array $t, array $ins, array $age, string $level, array $cats, int $seq): void
    {
        $price = $t['priceCourse'][$seq % count($t['priceCourse'])];
        $lessons = count($t['curriculum']);
        $totalMin = $lessons * 25;

        $name = sprintf('%s — Self-Paced %s Course · %s', $t['topic'], ucfirst($level), $ins['name']);
        $shortDesc = sprintf('%d on-demand lessons · %.1f hours · Ages %d–%d · taught by %s', $lessons, $totalMin/60, $age[0], $age[1], $ins['name']);
        $longDesc = $this->longDescCourse($t, $ins, $age, $level);

        $id = $this->makeProduct($sku, $name, 'course', $price, (int) round($price * 1.5), $age[0], $age[1], $cats, $shortDesc, $longDesc);
        if (! $id) return;

        $this->db->table('courses')->ignore(true)->insert([
            'product_id'           => $id,
            'instructor_name'      => $ins['name'],
            'instructor_bio'       => "{$ins['name']} — {$ins['cred']}, {$ins['years']} years teaching kids. Khoobie-curated educator.",
            'language'             => 'English',
            'level'                => $level,
            'total_minutes'        => $totalMin,
            'lessons_count'        => $lessons,
            'what_youll_learn'     => json_encode($t['deliverables']),
            'access_days'          => 365,
            'certificate_available'=> 1,
        ]);
        $courseId = (int) $this->db->table('courses')->where('product_id', $id)->get()->getRow()->id;

        // Group lessons into 3 modules for a cleaner curriculum tree
        $chunks = array_chunk($t['curriculum'], (int) ceil($lessons / 3));
        $modSort = 10;
        foreach ($chunks as $idx => $chunkLessons) {
            $this->db->table('course_modules')->insert([
                'course_id'  => $courseId,
                'title'      => 'Module ' . ($idx + 1) . ': ' . ($idx === 0 ? 'Foundation' : ($idx === count($chunks) - 1 ? 'Mastery' : 'Building Skills')),
                'description'=> null,
                'sort_order' => $modSort,
            ]);
            $modId = (int) $this->db->insertID();
            $lessonSort = 10;
            foreach ($chunkLessons as $lIdx => $title) {
                $this->db->table('course_lessons')->insert([
                    'module_id'        => $modId,
                    'title'            => $title,
                    'video_url'        => 'https://www.youtube.com/embed/M7lc1UVf-VE',
                    'video_provider'   => 'youtube',
                    'duration_minutes' => 20 + ($lIdx * 3),
                    'is_preview'       => ($idx === 0 && $lIdx === 0) ? 1 : 0,
                    'sort_order'       => $lessonSort,
                ]);
                $lessonSort += 10;
            }
            $modSort += 10;
        }
    }

    private function mkMeetup(string $sku, array $t, array $ins, array $age, string $level, array $city, array $cats, int $seq): void
    {
        $price = $t['priceMeetup'][$seq % count($t['priceMeetup'])];
        // Set start date 7-90 days in future, varied by seq
        $daysOut = 7 + (($seq * 13) % 80);
        $hour    = [10, 11, 14, 15, 16][$seq % 5];
        $starts  = date('Y-m-d', strtotime("+{$daysOut} days")) . sprintf(' %02d:00:00', $hour);
        $ends    = date('Y-m-d H:i:s', strtotime($starts) + (90 * 60));

        // Show locality in the product name so search results read like "Karate — Rohini, Delhi · 14 Jun"
        $where = sprintf('%s, %s', $city['locality'] ?? $city['city'], $city['city']);
        $name = sprintf('%s — %s · %s · %s', $t['topic'], $where, date('j M', strtotime($starts)), $ins['name']);
        $shortDesc = sprintf('Live in-person · %s › %s › %s · %s · Ages %d–%d · ₹%s',
            $city['city'], $city['locality'] ?? '', $city['area'] ?? '',
            date('j M Y, g A', strtotime($starts)), $age[0], $age[1], number_format(round($price / 100))
        );
        $longDesc = $this->longDescMeetup($t, $ins, $age, $level, $city, $starts);

        $id = $this->makeProduct($sku, $name, 'meetup', $price, (int) round($price * 1.3), $age[0], $age[1], $cats, $shortDesc, $longDesc);
        if (! $id) return;

        $agenda = array_map(fn ($title, $i) => ['time' => sprintf('%02d:%02d', $hour + intdiv($i*15, 60), ($i*15) % 60), 'item' => $title], $t['curriculum'], array_keys($t['curriculum']));

        $address = sprintf('%s, %s, %s — %s',
            $city['area']     ?? '',
            $city['locality'] ?? '',
            $city['city'],
            $city['pincode']
        );

        $this->db->table('meetups')->ignore(true)->insert([
            'product_id'    => $id,
            'location_name' => sprintf('Khoobie Studio Partner — %s, %s', $city['locality'] ?? $city['city'], $city['city']),
            'address'       => $address,
            'city'          => $city['city'],
            'locality'      => $city['locality'] ?? null,
            'area'          => $city['area'] ?? null,
            'state'         => $city['state'],
            'pincode'       => $city['pincode'],
            'latitude'      => $city['lat'],
            'longitude'     => $city['lng'],
            'maps_url'      => sprintf("https://www.google.com/maps/search/%s+%s+%s",
                rawurlencode($city['area'] ?? ''),
                rawurlencode($city['locality'] ?? ''),
                rawurlencode($city['city'])
            ),
            'starts_at'     => $starts,
            'ends_at'       => $ends,
            'capacity'      => 15,
            'is_free'       => 0,
            'rsvp_required' => 1,
            'host_name'     => $ins['name'],
            'host_phone'    => '+91-9' . str_pad((string) ($seq * 1234567 % 1000000000), 9, '0', STR_PAD_LEFT),
            'agenda'        => json_encode($agenda),
            'status'        => 'published',
        ]);
    }

    private function mkService(string $sku, array $t, array $ins, array $age, string $level, array $cats, int $seq, string $topicKey): void
    {
        $price = $t['priceService'][$seq % count($t['priceService'])];
        $kindMap = ['chess'=>'tutoring','public-speaking'=>'consultation','pottery'=>'custom','yoga'=>'consultation','spoken-english'=>'tutoring','classical-vocal'=>'tutoring','bharatanatyam'=>'tutoring'];
        $kind = $kindMap[$topicKey] ?? 'tutoring';

        $name = sprintf('%s — 1-on-1 with %s · Per Session', $t['topic'], $ins['name']);
        $shortDesc = sprintf('Private 60-min session · Ages %d–%d · taught by %s (%s)', $age[0], $age[1], $ins['name'], $ins['cred']);
        $longDesc = $this->longDescService($t, $ins, $age, $level);

        $id = $this->makeProduct($sku, $name, 'service', $price, (int) round($price * 1.3), $age[0], $age[1], $cats, $shortDesc, $longDesc);
        if (! $id) return;

        $this->db->table('services')->ignore(true)->insert([
            'product_id'      => $id,
            'service_kind'    => in_array($kind, ['tutoring','consultation','party_planning','toy_rental','custom']) ? $kind : 'tutoring',
            'provider_name'   => $ins['name'],
            'duration_minutes'=> 60,
            'modality'        => ($seq % 3 === 0) ? 'at_home' : 'online',
            'is_active'       => 1,
        ]);

        // Open 4 future slots
        $serviceId = (int) $this->db->table('services')->where('product_id', $id)->get()->getRow()->id;
        for ($i = 1; $i <= 4; $i++) {
            $startTs = strtotime("+{$i} days") + 17 * 3600 + ($seq % 4) * 1800;
            $this->db->table('service_slots')->insert([
                'service_id' => $serviceId,
                'starts_at'  => date('Y-m-d H:i:s', $startTs),
                'ends_at'    => date('Y-m-d H:i:s', $startTs + 3600),
                'is_booked'  => 0,
            ]);
        }
    }

    // ====================================================================
    // LONG DESCRIPTION TEMPLATES — what shows up in the PDP "Long description"
    // ====================================================================
    private function longDescTuition(array $t, array $ins, array $age, string $level, array $sched, string $modality): string
    {
        $curriculum = "<ul>" . implode("", array_map(fn ($l) => "<li>{$l}</li>", $t['curriculum'])) . "</ul>";
        $deliverables = "<ul>" . implode("", array_map(fn ($d) => "<li>{$d}</li>", $t['deliverables'])) . "</ul>";
        $modeLabel = $modality === 'online' ? 'Live online via Zoom' : ($modality === 'offline' ? 'In-person' : 'Hybrid (online + monthly in-person meet)');
        $schedLabel = implode(', ', $sched['days']) . ' · ' . substr($sched['start'],0,5) . '–' . substr($sched['end'],0,5);

        return "<h3>About this class</h3>
<p>A small-group <strong>" . ucfirst($level) . "</strong> level " . strtolower($t['topic']) . " program designed for kids aged <strong>{$age[0]}–{$age[1]}</strong>. {$modeLabel}, {$schedLabel}.</p>

<h3>Your instructor</h3>
<p><strong>{$ins['name']}</strong> — {$ins['cred']}. {$ins['years']} years teaching kids. Khoobie-vetted educator with parent-reviewed track record.</p>

<h3>Curriculum (10 weeks)</h3>
{$curriculum}

<h3>What you take home</h3>
{$deliverables}

<h3>How billing works</h3>
<p>Monthly auto-renewing fee, cancel anytime. <strong>Free trial class</strong> available — book first, decide later. Khoobie holds the fee for the first 7 days and refunds in full if not satisfied.</p>";
    }

    private function longDescCourse(array $t, array $ins, array $age, string $level): string
    {
        $curriculum = "<ul>" . implode("", array_map(fn ($l) => "<li>{$l}</li>", $t['curriculum'])) . "</ul>";
        $deliverables = "<ul>" . implode("", array_map(fn ($d) => "<li>{$d}</li>", $t['deliverables'])) . "</ul>";

        return "<h3>About this course</h3>
<p>A <strong>self-paced video course</strong> on " . strtolower($t['topic']) . " for kids aged <strong>{$age[0]}–{$age[1]}</strong>, " . ucfirst($level) . " level. Watch on any device, replay forever, progress at your own speed.</p>

<h3>Your instructor</h3>
<p><strong>{$ins['name']}</strong> — {$ins['cred']}, {$ins['years']} years experience.</p>

<h3>What's inside</h3>
{$curriculum}

<h3>What you get</h3>
{$deliverables}

<h3>Access & guarantee</h3>
<p>365-day full access. Certificate on completion. <strong>7-day money-back guarantee</strong> — no questions asked.</p>";
    }

    private function longDescMeetup(array $t, array $ins, array $age, string $level, array $city, string $starts): string
    {
        $agenda = "<ul>" . implode("", array_map(fn ($l) => "<li>{$l}</li>", $t['curriculum'])) . "</ul>";
        $deliverables = "<ul>" . implode("", array_map(fn ($d) => "<li>{$d}</li>", $t['deliverables'])) . "</ul>";

        return "<h3>About this meetup</h3>
<p>A live in-person workshop in <strong>{$city['city']}</strong> on <strong>" . date('l, j F Y', strtotime($starts)) . "</strong> at <strong>" . date('g:i A', strtotime($starts)) . "</strong>. Ages <strong>{$age[0]}–{$age[1]}</strong>. 90 minutes of pure hands-on fun.</p>

<h3>Your host</h3>
<p><strong>{$ins['name']}</strong> — {$ins['cred']}, {$ins['years']} years of experience leading kids' workshops.</p>

<h3>Agenda</h3>
{$agenda}

<h3>What's included</h3>
{$deliverables}

<h3>Cancellation</h3>
<p>Full refund up to 48 hours before. Materials, instruction & light refreshments included in the fee.</p>";
    }

    private function longDescService(array $t, array $ins, array $age, string $level): string
    {
        $deliverables = "<ul>" . implode("", array_map(fn ($d) => "<li>{$d}</li>", $t['deliverables'])) . "</ul>";

        return "<h3>About this 1-on-1 session</h3>
<p>A <strong>private 60-minute session</strong> on " . strtolower($t['topic']) . " for kids aged <strong>{$age[0]}–{$age[1]}</strong>, " . ucfirst($level) . " level. Fully customised to your child's pace and interest.</p>

<h3>Your mentor</h3>
<p><strong>{$ins['name']}</strong> — {$ins['cred']}, {$ins['years']} years of teaching kids 1-on-1.</p>

<h3>What you get</h3>
{$deliverables}

<h3>How it works</h3>
<ol>
  <li>Pick a slot from the schedule above</li>
  <li>Pre-session call to align on goals</li>
  <li>60-minute focused session (online via Zoom or at-home in select cities)</li>
  <li>Written feedback + recommended next steps</li>
</ol>";
    }

    // ====================================================================
    // SHARED PRODUCT CREATION — same shape as EcosystemSeeder::makeProduct
    // ====================================================================
    private function makeProduct(string $sku, string $name, string $type, int $price, int $compare, int $ageMin, int $ageMax, array $categoryIds, string $shortDesc, string $longDesc): ?int
    {
        $db = $this->db;
        if ($db->table('products')->where('sku', $sku)->countAllResults() > 0) {
            return (int) $db->table('products')->where('sku', $sku)->get()->getRow()->id;
        }
        $slug = url_title(strtolower($name), '-', true);
        // Ensure slug uniqueness (collisions possible across SKUs with similar names)
        $base = $slug; $n = 2;
        while ($db->table('products')->where('slug', $slug)->countAllResults() > 0) {
            $slug = $base . '-' . $n++;
        }

        $hero = "https://picsum.photos/seed/{$sku}/900/900";
        $gallery = [
            "https://picsum.photos/seed/{$sku}-a/900/900",
            "https://picsum.photos/seed/{$sku}-b/900/900",
            "https://picsum.photos/seed/{$sku}-c/900/900",
        ];
        $rich = [
            ['type' => 'usp_grid', 'items' => [
                ['icon' => '🎓', 'title' => 'Khoobie-vetted',  'desc' => 'Background-checked instructors'],
                ['icon' => '🆓', 'title' => 'Free trial class', 'desc' => 'Try before you commit'],
                ['icon' => '🛡️','title' => 'Safe & small batch','desc' => 'Max 12 students per class'],
                ['icon' => '💯', 'title' => '7-day guarantee', 'desc' => 'Full refund if not satisfied'],
            ]],
        ];

        $db->table('products')->insert([
            'sku'             => $sku,
            'slug'            => $slug,
            'name'            => $name,
            'type'            => $type,
            'short_desc'      => $shortDesc,
            'long_desc'       => $longDesc,
            'hero_image'      => $hero,
            'gallery'         => json_encode($gallery),
            'status'          => 'active',
            'is_featured'     => random_int(0, 9) === 0 ? 1 : 0,   // 10% featured
            'tax_class_id'    => null,
            'hsn_code'        => '9992',                            // education services
            'age_min_years'   => $ageMin,
            'age_max_years'   => $ageMax,
            'rating_avg'      => round(4.2 + (random_int(0, 6) / 10), 2),
            'rating_count'    => random_int(12, 80),
            'sales_count'     => random_int(8, 250),
            'seo_title'       => $name . ' | Khoobie Classes',
            'seo_description' => substr(strip_tags($shortDesc), 0, 160),
            'rich_blocks'     => json_encode($rich),
            'published_at'    => date('Y-m-d H:i:s'),
        ]);
        $pid = (int) $db->insertID();

        foreach ($categoryIds as $cid) {
            if ($cid) $db->table('product_categories')->ignore(true)->insert(['product_id' => $pid, 'category_id' => $cid]);
        }

        // Default variant — required for Add-to-Cart to work
        $db->table('product_variants')->insert([
            'product_id'       => $pid,
            'sku'              => $sku . '-V1',
            'name'             => 'Default',
            'price'            => $price,
            'compare_at_price' => $compare > $price ? $compare : null,
            'is_default'       => 1,
            'is_active'        => 1,
        ]);

        return $pid;
    }
}
