<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Populates the storefront with a realistic-feeling catalog:
 * - 6 parent categories
 * - 15 leaf categories nested under them
 * - 12 products per leaf = 180 products
 * - Each product has hero image + 3-image gallery (picsum.photos seeded)
 * - 30% of products get a sample YouTube embed video
 * - All products get A+ rich_blocks for the PDP
 * - Default variant + inventory row + HSN code
 *
 * SAFE to re-run: clears the existing catalog before re-inserting.
 */
class RealisticCatalogSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        // ---------- 1. Clean existing catalog (FK-safe order) ----------
        $db->disableForeignKeyChecks();
        foreach ([
            'inventory_movements','inventory','digital_assets','bundle_items',
            'product_related','product_attributes','variant_attributes',
            'product_categories','product_variants','products','categories',
        ] as $t) {
            $db->table($t)->truncate();
        }
        $db->enableForeignKeyChecks();

        // ---------- 2. Category tree ----------
        $tree = [
            ['slug' => 'board-games',     'name' => 'Board Games',     'icon' => '🎲', 'children' => [
                ['slug' => 'strategy-board-games', 'name' => 'Strategy Board Games'],
                ['slug' => 'family-party-games',   'name' => 'Family & Party Games'],
                ['slug' => 'classic-board-games',  'name' => 'Classic Board Games'],
            ]],
            ['slug' => 'books',           'name' => 'Books',           'icon' => '📚', 'children' => [
                ['slug' => 'story-books',          'name' => 'Story Books'],
                ['slug' => 'activity-books',       'name' => 'Activity Books'],
                ['slug' => 'reference-books',      'name' => 'Reference & Learning Books'],
            ]],
            ['slug' => 'experiments',     'name' => 'Science Experiments', 'icon' => '🧪', 'children' => [
                ['slug' => 'chemistry-kits',       'name' => 'Chemistry Kits'],
                ['slug' => 'physics-experiments',  'name' => 'Physics Experiments'],
            ]],
            ['slug' => 'project-kits',    'name' => 'Project Kits',    'icon' => '🛠️', 'children' => [
                ['slug' => 'robotics-kits',        'name' => 'Robotics Kits'],
                ['slug' => 'arts-and-crafts',      'name' => 'Arts & Crafts Kits'],
                ['slug' => 'paper-craft-kits',     'name' => 'Paper Craft Kits'],
            ]],
            ['slug' => 'puzzles',         'name' => 'Puzzles',         'icon' => '🧩', 'children' => [
                ['slug' => 'jigsaw-puzzles',       'name' => 'Jigsaw Puzzles'],
                ['slug' => 'brain-teasers',        'name' => '3D Puzzles & Brain Teasers'],
            ]],
            ['slug' => 'digital-classes', 'name' => 'Digital & Classes', 'icon' => '💻', 'children' => [
                ['slug' => 'digital-downloads',    'name' => 'Digital Downloads'],
                ['slug' => 'live-classes',         'name' => 'Live Online Classes'],
            ]],
        ];

        $catId = [];
        $sort = 10;
        foreach ($tree as $parent) {
            $db->table('categories')->insert([
                'parent_id'   => null,
                'slug'        => $parent['slug'],
                'name'        => $parent['name'],
                'description' => $this->categoryDescription($parent['name']),
                'icon'        => $parent['icon'],
                'sort_order'  => $sort,
                'is_active'   => 1,
                'seo_title'   => $parent['name'] . ' — Krafty Khoobie',
                'seo_description' => 'Shop ' . strtolower($parent['name']) . ' from Krafty Khoobie. Screen-free fun, hand-picked for curious kids.',
            ]);
            $catId[$parent['slug']] = (int) $db->insertID();
            $sort += 10;

            $childSort = 10;
            foreach ($parent['children'] as $child) {
                $db->table('categories')->insert([
                    'parent_id'   => $catId[$parent['slug']],
                    'slug'        => $child['slug'],
                    'name'        => $child['name'],
                    'description' => $this->categoryDescription($child['name']),
                    'sort_order'  => $childSort,
                    'is_active'   => 1,
                    'seo_title'   => $child['name'] . ' — Krafty Khoobie',
                    'seo_description' => 'Discover ' . strtolower($child['name']) . ' for kids on Krafty Khoobie.',
                ]);
                $catId[$child['slug']] = (int) $db->insertID();
                $childSort += 10;
            }
        }

        // ---------- 3. Tax classes + warehouse refs ----------
        $tax12 = $db->table('tax_classes')->where('slug', 'gst-12')->get()->getRow();
        $taxId = $tax12 ? (int) $tax12->id : null;
        $wh = $db->table('warehouses')->where('is_default', 1)->get()->getRow();
        $whId = $wh ? (int) $wh->id : null;

        // ---------- 4. Product catalog ----------
        // 15 leaf categories with 12 product names each = 180 products
        $catalog = $this->productCatalog();
        $videos = $this->demoVideos();

        $productCounter = 1;
        foreach ($catalog as $leafSlug => $info) {
            [$priceMin, $priceMax, $ageMin, $ageMax, $hsn, $names] = [
                $info['price_min'], $info['price_max'],
                $info['age_min'],   $info['age_max'],
                $info['hsn'],       $info['names'],
            ];

            // Find parent slug to also tag products there (so parent category page lists them)
            $parentSlug = $this->parentOf($tree, $leafSlug);

            foreach ($names as $idx => $name) {
                $slug = url_title(strtolower($name), '-', true);
                $sku  = 'KK-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $leafSlug), 0, 3)) . '-' . str_pad((string) $productCounter, 4, '0', STR_PAD_LEFT);

                // Price within range (in paise)
                $price   = (int) (random_int($priceMin, $priceMax) * 100);
                $compare = (int) ($price * (1 + (random_int(15, 45) / 100))); // 15-45% MRP markup
                // Round nicely
                $price   = (int) (round($price / 5000) * 5000) ?: $price;
                $compare = (int) (round($compare / 5000) * 5000) ?: $compare;

                $rating = round(3.8 + (mt_rand() / mt_getrandmax()) * 1.1, 1); // 3.8 - 4.9
                $ratingCount = random_int(8, 240);
                $salesCount = random_int(5, 500);

                // Images — picsum.photos seeded so they're stable across reloads
                $heroImage = "https://picsum.photos/seed/{$slug}/900/900";
                $gallery = [
                    "https://picsum.photos/seed/{$slug}-a/900/900",
                    "https://picsum.photos/seed/{$slug}-b/900/900",
                    "https://picsum.photos/seed/{$slug}-c/900/900",
                    "https://picsum.photos/seed/{$slug}-d/900/900",
                ];

                // ~30% get a video
                $videoUrl = ($productCounter % 3 === 0) ? $videos[$productCounter % count($videos)] : null;

                $isFeatured = ($idx < 2) ? 1 : 0; // First 2 of each leaf get featured

                $shortDesc = $this->shortDesc($leafSlug, $name);
                $longDesc  = $this->longDesc($leafSlug, $name, $ageMin, $ageMax);
                $richBlocks = $this->richBlocks($leafSlug, $name, $heroImage, $videoUrl);

                $db->table('products')->insert([
                    'sku'           => $sku,
                    'slug'          => $slug,
                    'name'          => $name,
                    'type'          => $leafSlug === 'digital-downloads' ? 'digital' : ($leafSlug === 'live-classes' ? 'event' : 'simple'),
                    'short_desc'    => $shortDesc,
                    'long_desc'     => $longDesc,
                    'hero_image'    => $heroImage,
                    'gallery'       => json_encode($gallery),
                    'video_url'     => $videoUrl,
                    'status'        => 'active',
                    'is_featured'   => $isFeatured,
                    'tax_class_id'  => $taxId,
                    'hsn_code'      => $hsn,
                    'age_min_years' => $ageMin,
                    'age_max_years' => $ageMax,
                    'rating_avg'    => $rating,
                    'rating_count'  => $ratingCount,
                    'sales_count'   => $salesCount,
                    'seo_title'     => $name . ' | ' . $info['display'] . ' | Krafty Khoobie',
                    'seo_description' => substr($shortDesc, 0, 160),
                    'rich_blocks'   => json_encode($richBlocks),
                    'published_at'  => date('Y-m-d H:i:s'),
                ]);
                $productId = (int) $db->insertID();

                // Map to leaf + parent category
                $db->table('product_categories')->insert(['product_id' => $productId, 'category_id' => $catId[$leafSlug], 'sort_order' => $idx]);
                if ($parentSlug && isset($catId[$parentSlug])) {
                    $db->table('product_categories')->insert(['product_id' => $productId, 'category_id' => $catId[$parentSlug], 'sort_order' => $idx]);
                }

                // Default variant
                $db->table('product_variants')->insert([
                    'product_id'       => $productId,
                    'sku'              => $sku . '-V1',
                    'name'             => 'Default',
                    'price'            => $price,
                    'compare_at_price' => $compare,
                    'cost'             => (int) ($price * 0.55),
                    'weight_g'         => random_int(150, 1200),
                    'is_default'       => 1,
                    'is_active'        => 1,
                ]);
                $variantId = (int) $db->insertID();

                // Inventory (skip for digital / event)
                if (! in_array($leafSlug, ['digital-downloads', 'live-classes'], true) && $whId) {
                    $db->table('inventory')->insert([
                        'variant_id'   => $variantId,
                        'warehouse_id' => $whId,
                        'qty_on_hand'  => random_int(0, 120),
                        'reorder_level'=> 10,
                    ]);
                }

                // Digital asset
                if ($leafSlug === 'digital-downloads') {
                    $db->table('digital_assets')->insert([
                        'product_id'   => $productId,
                        'variant_id'   => $variantId,
                        'name'         => $name . ' — Download',
                        'file_path'    => 'digital/placeholder.pdf',
                        'license_type' => 'personal',
                        'expiry_days'  => 90,
                    ]);
                }

                // Product attributes (key features for filters / specs)
                foreach ($this->productAttributes($leafSlug) as $attr) {
                    $db->table('product_attributes')->insert([
                        'product_id'    => $productId,
                        'group_key'     => $attr['group'],
                        'key'           => $attr['key'],
                        'value'         => $attr['value'],
                        'is_filterable' => $attr['filter'],
                    ]);
                }

                $productCounter++;
            }
        }

        // ---------- 5. Cross-link related products (within same leaf) ----------
        $allByCategory = $db->table('product_categories pc')
            ->join('categories c', 'c.id = pc.category_id')
            ->select('pc.product_id, c.slug')
            ->where('c.parent_id IS NOT NULL') // leaf only
            ->get()->getResultArray();
        $byLeaf = [];
        foreach ($allByCategory as $r) $byLeaf[$r['slug']][] = (int) $r['product_id'];
        foreach ($byLeaf as $slug => $ids) {
            foreach ($ids as $pid) {
                $others = array_values(array_diff($ids, [$pid]));
                shuffle($others);
                foreach (array_slice($others, 0, 4) as $i => $rid) {
                    $type = $i < 2 ? 'cross_sell' : 'upsell';
                    $db->table('product_related')->ignore(true)->insert([
                        'product_id' => $pid, 'related_product_id' => $rid,
                        'type' => $type, 'sort_order' => $i,
                    ]);
                }
            }
        }
    }

    // -------------------- Helpers --------------------

    private function parentOf(array $tree, string $childSlug): ?string
    {
        foreach ($tree as $p) {
            foreach ($p['children'] as $c) {
                if ($c['slug'] === $childSlug) return $p['slug'];
            }
        }
        return null;
    }

    private function categoryDescription(string $name): string
    {
        return "Discover {$name} on Krafty Khoobie — hand-picked, age-appropriate, screen-free joys that keep kids learning and laughing.";
    }

    private function demoVideos(): array
    {
        // Public YouTube product-demo / educational embeds (CC or owned-channel content)
        return [
            'https://www.youtube.com/embed/M7lc1UVf-VE', // Google I/O Spotlight (placeholder)
            'https://www.youtube.com/embed/aqz-KE-bpKQ', // Big Buck Bunny (CC public)
            'https://www.youtube.com/embed/eY52Zsg-KVI', // Generic educational
            'https://www.youtube.com/embed/ScMzIvxBSi4', // Demo product video
            'https://www.youtube.com/embed/jNQXAC9IVRw', // First YouTube video (Me at the zoo)
            'https://www.youtube.com/embed/Y-x0efG1seA', // generic
        ];
    }

    private function shortDesc(string $cat, string $name): string
    {
        $templates = [
            'strategy-board-games' => 'A brain-stretching board game that grows critical thinking, planning and friendly rivalry.',
            'family-party-games'   => 'A laugh-out-loud party game for the whole family — no screens, just smiles.',
            'classic-board-games'  => 'A timeless board game classic, refreshed for a new generation of family game nights.',
            'story-books'          => 'A beautifully illustrated storybook that sparks imagination at bedtime and beyond.',
            'activity-books'       => 'Hours of guided activity, drawing, puzzling and discovery — perfect for travel or rainy days.',
            'reference-books'      => 'A reference book curious kids will return to again and again.',
            'chemistry-kits'       => 'Safe, supervised chemistry experiments with everything included — no kitchen raid needed.',
            'physics-experiments'  => 'Hands-on physics that turns "why?" into "wow!" with every experiment.',
            'robotics-kits'        => 'Build, code and play with a robotics kit designed for first-time engineers.',
            'arts-and-crafts'      => 'A complete kit — supplies, ideas and step-by-step prompts to create with confidence.',
            'paper-craft-kits'     => 'Fold, glue and create gorgeous paper crafts with kid-friendly instructions.',
            'jigsaw-puzzles'       => 'A beautifully illustrated jigsaw puzzle that builds focus, patience and pattern skills.',
            'brain-teasers'        => 'A tactile 3D puzzle that challenges spatial reasoning and quick thinking.',
            'digital-downloads'    => 'Instant download — printable activity pack delivered to your inbox.',
            'live-classes'         => 'A live, instructor-led online class your child can join from anywhere.',
        ];
        return $templates[$cat] ?? 'A screen-free favourite from Krafty Khoobie.';
    }

    private function longDesc(string $cat, string $name, int $ageMin, int $ageMax): string
    {
        $intro = "{$name} is one of our most-loved screen-free picks at Krafty Khoobie.";
        $age   = "Designed for ages {$ageMin}-{$ageMax}, it's been tested with real Indian families and refined for the way kids actually play.";
        $value = "Every Krafty Khoobie product is chosen by parent-educators, never sourced from a generic catalogue.";
        $promise = "We obsess over the small details — quality of materials, clarity of instructions, time-to-fun — so every minute your child spends with it is a minute they're not staring at a screen.";
        return implode("\n\n", [$intro, $age, $value, $promise]);
    }

    private function richBlocks(string $cat, string $name, string $heroImage, ?string $videoUrl): array
    {
        $blocks = [
            ['type' => 'hero', 'image' => $heroImage, 'headline' => $name, 'sub' => 'Screen-free fun, lovingly crafted in India'],
            ['type' => 'usp_grid', 'items' => [
                ['icon' => '🛡️', 'title' => 'Kid-safe materials', 'desc' => 'Non-toxic, edge-rounded, child-tested'],
                ['icon' => '📦', 'title' => 'Free shipping ₹999+', 'desc' => 'Across India, with COD on most pincodes'],
                ['icon' => '↩️', 'title' => '7-day returns', 'desc' => 'No questions asked'],
                ['icon' => '👨‍👩‍👧', 'title' => 'Parent-curated', 'desc' => 'Picked by Khoobie\'s parent-educator team'],
            ]],
        ];
        if ($videoUrl) {
            $blocks[] = ['type' => 'video', 'url' => $videoUrl, 'caption' => 'Watch it in action'];
        }
        $blocks[] = ['type' => 'faq', 'items' => [
            ['q' => 'How long does shipping take?', 'a' => '2-6 business days across India. Tracking shared via WhatsApp + email.'],
            ['q' => 'Is it really screen-free?', 'a' => 'Yes — everything in this product invites your child off the screen and into hands-on play.'],
            ['q' => 'Can I return it?', 'a' => 'Of course. 7-day no-questions-asked returns from delivery date.'],
        ]];
        return $blocks;
    }

    private function productAttributes(string $cat): array
    {
        $base = [
            ['group' => 'features',  'key' => 'Screen-free',      'value' => 'Yes',                'filter' => 1],
            ['group' => 'features',  'key' => 'Country of origin','value' => 'India',              'filter' => 0],
            ['group' => 'shipping',  'key' => 'Ships in',         'value' => '24 hours',           'filter' => 0],
        ];
        $perCat = [
            'strategy-board-games' => [
                ['group' => 'specs',  'key' => 'Players',  'value' => '2-4',           'filter' => 1],
                ['group' => 'specs',  'key' => 'Playtime', 'value' => '20-40 minutes', 'filter' => 1],
            ],
            'family-party-games'   => [
                ['group' => 'specs',  'key' => 'Players',  'value' => '4-10',         'filter' => 1],
                ['group' => 'specs',  'key' => 'Playtime', 'value' => '15 minutes',   'filter' => 1],
            ],
            'classic-board-games'  => [
                ['group' => 'specs',  'key' => 'Players',  'value' => '2-6',          'filter' => 1],
                ['group' => 'specs',  'key' => 'Playtime', 'value' => '30 minutes',   'filter' => 1],
            ],
            'story-books'          => [
                ['group' => 'specs',  'key' => 'Pages',    'value' => '32 pages',     'filter' => 0],
                ['group' => 'specs',  'key' => 'Language', 'value' => 'English',      'filter' => 1],
            ],
            'activity-books'       => [
                ['group' => 'specs',  'key' => 'Pages',    'value' => '64 pages',     'filter' => 0],
                ['group' => 'specs',  'key' => 'Format',   'value' => 'Spiral bound', 'filter' => 0],
            ],
            'reference-books'      => [
                ['group' => 'specs',  'key' => 'Pages',    'value' => '96 pages',     'filter' => 0],
                ['group' => 'specs',  'key' => 'Cover',    'value' => 'Hardcover',    'filter' => 0],
            ],
            'chemistry-kits'       => [
                ['group' => 'specs',  'key' => 'Experiments', 'value' => '15+',       'filter' => 1],
                ['group' => 'specs',  'key' => 'Safety',      'value' => 'Adult-supervised', 'filter' => 0],
            ],
            'physics-experiments'  => [
                ['group' => 'specs',  'key' => 'Experiments', 'value' => '10+',       'filter' => 1],
                ['group' => 'specs',  'key' => 'Skill focus', 'value' => 'STEM',      'filter' => 1],
            ],
            'robotics-kits'        => [
                ['group' => 'specs',  'key' => 'Skill focus', 'value' => 'Robotics + Coding', 'filter' => 1],
                ['group' => 'specs',  'key' => 'Reusable',    'value' => 'Yes',       'filter' => 0],
            ],
            'arts-and-crafts'      => [
                ['group' => 'specs',  'key' => 'Materials',   'value' => '20+ included', 'filter' => 0],
                ['group' => 'specs',  'key' => 'Skill focus', 'value' => 'Creativity',  'filter' => 1],
            ],
            'paper-craft-kits'     => [
                ['group' => 'specs',  'key' => 'Sheets',      'value' => '40+',       'filter' => 0],
                ['group' => 'specs',  'key' => 'Skill focus', 'value' => 'Fine motor',  'filter' => 1],
            ],
            'jigsaw-puzzles'       => [
                ['group' => 'specs',  'key' => 'Pieces',      'value' => '100-500',   'filter' => 1],
                ['group' => 'specs',  'key' => 'Finished size', 'value' => 'A2',      'filter' => 0],
            ],
            'brain-teasers'        => [
                ['group' => 'specs',  'key' => 'Difficulty',  'value' => 'Medium',    'filter' => 1],
                ['group' => 'specs',  'key' => 'Material',    'value' => 'Wood',      'filter' => 1],
            ],
            'digital-downloads'    => [
                ['group' => 'specs',  'key' => 'Format',      'value' => 'PDF',       'filter' => 1],
                ['group' => 'specs',  'key' => 'Pages',       'value' => '10-25',     'filter' => 0],
            ],
            'live-classes'         => [
                ['group' => 'specs',  'key' => 'Duration',    'value' => '45 min',    'filter' => 1],
                ['group' => 'specs',  'key' => 'Platform',    'value' => 'Zoom',      'filter' => 0],
            ],
        ];
        return array_merge($base, $perCat[$cat] ?? []);
    }

    /**
     * The 15-leaf-category × 12-product catalogue.
     * Each leaf: price_min, price_max (in ₹), age range, HSN code, and 12 product names.
     */
    private function productCatalog(): array
    {
        return [
            'strategy-board-games' => [
                'display' => 'Strategy Board Games', 'price_min' => 599, 'price_max' => 1499, 'age_min' => 6, 'age_max' => 14, 'hsn' => '9504',
                'names' => [
                    'Word Wizard Spelling Adventure', 'Math Mastermind Tournament', 'Chess Champions Classic Set',
                    'Empire Builders Strategy Game', 'Code Crackers Logic Puzzle Game', 'Castle Conquest Board Game',
                    'Trade Routes of India Strategy Game', 'Pattern Pursuit Brain Game', 'Capital Cities of the World Game',
                    'Atlas Adventure World Geography Game', 'Logic Labyrinth Maze Game', 'Memory Master Championship Game',
                ],
            ],
            'family-party-games' => [
                'display' => 'Family & Party Games', 'price_min' => 399, 'price_max' => 999, 'age_min' => 5, 'age_max' => 14, 'hsn' => '9504',
                'names' => [
                    'Charades Junior Party Card Game', 'Pictionary Family Edition', 'Karaoke Kids Party Pack',
                    'Truth or Treasure Hunt Family Game', 'Family Quiz Night Box', 'Animal Sounds Memory Match',
                    'Funny Faces Photo Card Game', 'Bingo Buddies for Kids', 'Roll & Giggle Dice Party Game',
                    'Talent Show Family Showdown', 'Guess Who Family Pack', 'Whisper Down the Lane Party Game',
                ],
            ],
            'classic-board-games' => [
                'display' => 'Classic Board Games', 'price_min' => 299, 'price_max' => 899, 'age_min' => 4, 'age_max' => 12, 'hsn' => '9504',
                'names' => [
                    'Snakes & Ladders Classic Edition', 'Ludo Royal Wooden Edition', 'Carrom Board Junior Tournament',
                    'Checkers Travel Set', 'Tic-Tac-Toe Giant Floor Edition', 'Connect Four Family Classic',
                    'Memory Match Animal Edition', 'Dominoes Wooden Classic Set', 'Backgammon for Beginners',
                    'Snakes & Ladders Indian Mythology Edition', 'Ludo & Snakes Combo Wooden Board', 'Chess for Beginners Set',
                ],
            ],
            'story-books' => [
                'display' => 'Story Books', 'price_min' => 199, 'price_max' => 599, 'age_min' => 3, 'age_max' => 10, 'hsn' => '4901',
                'names' => [
                    'The Brave Little Mango Tree', 'Akbar & Birbal Adventures (Vol. 1)', 'The Curious Bunny Who Forgot to Hop',
                    'Pippi & the Pickle Jar Mystery', 'Tales from Grandma\'s Trunk (Vol. 1)', 'The Moon Who Was Lonely',
                    'How the Elephant Got Its Trunk', 'The Boy Who Spoke to Birds', 'Twelve Tales of Indian Festivals',
                    'The Princess and the Pothole', 'The Day the Clouds Forgot to Rain', 'Bedtime Tales for Tiny Tigers',
                ],
            ],
            'activity-books' => [
                'display' => 'Activity Books', 'price_min' => 199, 'price_max' => 499, 'age_min' => 4, 'age_max' => 10, 'hsn' => '4901',
                'names' => [
                    'Doodle Like a Khoobie Kid', 'Maze Mania — 50 Mazes for Curious Kids', 'Connect the Dots Adventure Book',
                    'Sticker Story Book — Make Your Own Tale', 'Colour Me Calm — Kids Mindfulness', 'Find the Hidden Animal Big Book',
                    'Spot the Difference Super Pack', 'Trace, Write, Wow! Letters & Numbers', 'My First Sudoku Sticker Book',
                    'Khoobie Holiday Activity Megapack', 'Wipe-Clean Activity Book — Reusable', 'Big Book of Why? Kids Q&A',
                ],
            ],
            'reference-books' => [
                'display' => 'Reference & Learning Books', 'price_min' => 349, 'price_max' => 999, 'age_min' => 6, 'age_max' => 14, 'hsn' => '4901',
                'names' => [
                    'The Khoobie Atlas of India', 'Big Book of Indian Inventors', 'My First Encyclopedia of Animals',
                    'How Things Work — A Kid\'s Guide', 'Khoobie Science Encyclopedia', 'Indian Mythology Stories for Kids',
                    'My First Dictionary — Illustrated', 'Khoobie Times Tables Mastery Book', 'The Wonder Book of World Wonders',
                    'Famous Indians Who Changed the World', 'A Kid\'s Guide to Birds of India', 'My First Book of Space',
                ],
            ],
            'chemistry-kits' => [
                'display' => 'Chemistry Kits', 'price_min' => 899, 'price_max' => 2499, 'age_min' => 8, 'age_max' => 14, 'hsn' => '9503',
                'names' => [
                    'Erupting Volcano Chemistry Lab', 'Crystal Growing Master Kit', 'Slime Lab Pro Edition',
                    'Bath Bomb Maker Chemistry Set', 'Soap Making Science Studio', 'Invisible Ink Spy Chemistry Kit',
                    'Magic Colour Changing Experiments', 'pH Detective Lab Kit', 'Kitchen Chemistry Adventure Pack',
                    'Glow in the Dark Chemistry Lab', 'Perfume Maker Chemistry Kit', '30 Safe Chemistry Experiments Box',
                ],
            ],
            'physics-experiments' => [
                'display' => 'Physics Experiments', 'price_min' => 799, 'price_max' => 2299, 'age_min' => 7, 'age_max' => 14, 'hsn' => '9503',
                'names' => [
                    'Build-Your-Own Solar System Kit', 'Magnetism Discovery Lab', 'Static Electricity Adventure Box',
                    'Wind Tunnel Physics Kit', 'Catapult Engineering Challenge', 'Roller Coaster Physics Builder',
                    'Newton\'s Laws Discovery Box', 'Light & Mirrors Experiment Kit', 'Sound Wave Science Studio',
                    'Pulley & Lever Engineering Kit', 'Optical Illusion Physics Box', '20 Physics Experiments at Home',
                ],
            ],
            'robotics-kits' => [
                'display' => 'Robotics Kits', 'price_min' => 1499, 'price_max' => 3999, 'age_min' => 8, 'age_max' => 15, 'hsn' => '9503',
                'names' => [
                    'My First Robot — Build & Code', 'Khoobie Bot Beginner Robotics Kit', 'Walking Robot Engineering Kit',
                    'Solar-Powered Robot Builder', 'Hydraulic Arm Engineering Kit', 'Line-Following Robot Starter Pack',
                    'Bluetooth Robot Programming Kit', 'Voice Activated Robot Builder', 'Drawing Robot Art & Code Kit',
                    'Maze Solving Robot Kit', 'Khoobie Junior Coding Robot', 'STEM Robotics Mega Pack',
                ],
            ],
            'arts-and-crafts' => [
                'display' => 'Arts & Crafts Kits', 'price_min' => 499, 'price_max' => 1499, 'age_min' => 4, 'age_max' => 12, 'hsn' => '9503',
                'names' => [
                    'Watercolour Painting Starter Kit', 'Pottery & Clay Sculpting Studio', 'Tie-and-Dye Fabric Art Kit',
                    'Friendship Bracelet Making Box', 'Embroidery for Beginners Kit', 'Khoobie Art Journaling Set',
                    'Spin Art Machine Studio', 'Wood Burning Pyrography Junior Kit', 'Sand Art Bottles Activity Set',
                    'Decoupage Craft Studio', 'Calligraphy for Kids Starter Box', 'Mosaic Art Mini Studio',
                ],
            ],
            'paper-craft-kits' => [
                'display' => 'Paper Craft Kits', 'price_min' => 299, 'price_max' => 799, 'age_min' => 4, 'age_max' => 12, 'hsn' => '9503',
                'names' => [
                    'Origami 100 Models Mega Pack', 'Paper Quilling Beginners Kit', 'Pop-Up Card Making Studio',
                    'Make Your Own Story Book Kit', 'Paper Plane Engineer\'s Lab', 'Paper Mache Animal Builders',
                    'Khoobie Diwali Decor Paper Kit', 'Greeting Card Designer Studio', 'Paper Bouquet Crafting Box',
                    'Make Your Own Diary Kit', 'Khoobie Calendar Designer Pack', '3D Paper Toy Builder Kit',
                ],
            ],
            'jigsaw-puzzles' => [
                'display' => 'Jigsaw Puzzles', 'price_min' => 299, 'price_max' => 999, 'age_min' => 3, 'age_max' => 12, 'hsn' => '9504',
                'names' => [
                    'Map of India Wooden Jigsaw', 'Solar System 100-Piece Puzzle', 'Indian Wildlife Forest Jigsaw',
                    'World Map for Curious Kids 200-Piece', 'Underwater Adventure 150-Piece Puzzle', 'Khoobie\'s Festival Map of India Jigsaw',
                    'Animals of the Jungle 50-Piece Wooden', 'Times Tables Puzzle Mat', 'Alphabet Wooden Floor Jigsaw',
                    'My First Khoobie Big Floor Puzzle', 'India\'s States Wooden Jigsaw Set', 'Famous Indian Landmarks 300-Piece',
                ],
            ],
            'brain-teasers' => [
                'display' => '3D Puzzles & Brain Teasers', 'price_min' => 299, 'price_max' => 899, 'age_min' => 7, 'age_max' => 14, 'hsn' => '9503',
                'names' => [
                    'Wooden Burr Puzzle 6-Piece', 'Magnetic Tangram Brain Teaser', 'Tower of Hanoi Wooden Classic',
                    'Lock & Key Logic Puzzles', '3D Sphere Twist Puzzle', 'Speed Cube 3×3 Tournament Grade',
                    'Khoobie Brain Twister Mega Pack', 'Wooden 4-in-1 Brain Puzzles', 'Snake Cube Logic Puzzle',
                    '24-Step Hanayama Cast Puzzle', 'IQ-Plus Pattern Puzzle Wooden Set', 'Khoobie 6-in-1 Brain Box',
                ],
            ],
            'digital-downloads' => [
                'display' => 'Digital Downloads', 'price_min' => 99, 'price_max' => 599, 'age_min' => 3, 'age_max' => 12, 'hsn' => '4820',
                'names' => [
                    '7-Day Screen-Free Activity Pack', 'Math Mastery Printable Workbook (Class 3)', 'English Grammar Workbook (Class 4)',
                    'Khoobie Printable Colouring Mega Pack', 'Holiday Activity Sheets — 50 Days', 'My First Sudoku Printable Pack',
                    'Trace & Write — Alphabets & Numbers', 'Indian Festivals Printable Activity Pack', 'Khoobie Story-a-Day Calendar PDF',
                    'Mindfulness & Yoga for Kids Printable', 'Khoobie\'s 100 Brain Teasers PDF', 'Spelling Bee Practice Book Class 5',
                ],
            ],
            'live-classes' => [
                'display' => 'Live Online Classes', 'price_min' => 299, 'price_max' => 1499, 'age_min' => 5, 'age_max' => 14, 'hsn' => '9992',
                'names' => [
                    'Live Storytelling Hour (Ages 5-7)', 'Live Yoga for Kids (Ages 6-10)', 'Live Coding for Beginners (Ages 8-12)',
                    'Live Art & Doodling Workshop', 'Live Math Puzzle Olympiad Prep', 'Live Indian Mythology Story Hour',
                    'Live Origami Workshop', 'Live Public Speaking Camp', 'Live Vedic Math Bootcamp',
                    'Live Creative Writing Camp', 'Live Robotics Live Camp', 'Live Music & Movement (Ages 4-6)',
                ],
            ],
        ];
    }
}
