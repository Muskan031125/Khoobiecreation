<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Khoobie Creations real-brand catalogue.
 *
 * Mirrors the categories and product style on khoobie.com:
 *   /collections/arts          → Learning Kits
 *   /collections/nature        → Nature Kits
 *   /collections/accessories   → Accessories
 *   /collections/return-gifts  → Return Gifts
 *
 * 4 parent categories × 3 sub-categories = 12 leaves × 12 products = 144 products,
 * with realistic Khoobie-style names (DIY Paint Kits, Wooden Cutouts, Garden Kits,
 * Pencil Cases, Return Gift bundles, etc.).
 *
 * SAFE to re-run: wipes catalog tables first.
 */
class KhoobieRealCatalogSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        // ---------- 1. Clean existing catalog ----------
        $db->disableForeignKeyChecks();
        foreach ([
            'inventory_movements','inventory','digital_assets','bundle_items',
            'product_related','product_attributes','variant_attributes',
            'product_categories','product_variants','products','categories',
        ] as $t) {
            $db->table($t)->truncate();
        }
        $db->enableForeignKeyChecks();

        // ---------- 2. Category tree matching khoobie.com ----------
        $tree = [
            ['slug' => 'arts',         'name' => 'Learning Kits', 'icon' => '🎨', 'children' => [
                ['slug' => 'diy-paint-kits',        'name' => 'DIY Paint Kits'],
                ['slug' => 'wooden-story-kits',     'name' => 'Wooden Story Kits'],
                ['slug' => 'art-craft-supplies',    'name' => 'Art & Craft Supplies'],
            ]],
            ['slug' => 'nature',       'name' => 'Nature Kits',   'icon' => '🌱', 'children' => [
                ['slug' => 'garden-grow-kits',      'name' => 'Garden Grow Kits'],
                ['slug' => 'edible-garden-kits',    'name' => 'Edible Garden Kits'],
                ['slug' => 'nature-explorer-kits',  'name' => 'Nature Explorer Kits'],
            ]],
            ['slug' => 'accessories',  'name' => 'Accessories',   'icon' => '🎒', 'children' => [
                ['slug' => 'pencil-cases-pouches',  'name' => 'Pencil Cases & Pouches'],
                ['slug' => 'personalized-items',    'name' => 'Personalized Items'],
                ['slug' => 'school-stationery',     'name' => 'School Stationery'],
            ]],
            ['slug' => 'return-gifts', 'name' => 'Return Gifts',  'icon' => '🎁', 'children' => [
                ['slug' => 'mini-paint-kits',       'name' => 'Mini Paint Kits'],
                ['slug' => 'party-favor-packs',     'name' => 'Party Favor Packs'],
                ['slug' => 'bulk-gift-combos',      'name' => 'Bulk Gift Combos'],
            ]],
        ];

        $catId = [];
        $sort = 10;
        foreach ($tree as $parent) {
            $db->table('categories')->insert([
                'parent_id'   => null,
                'slug'        => $parent['slug'],
                'name'        => $parent['name'],
                'description' => "Shop {$parent['name']} from Khoobie Creations — unique, handmade, screen-free craft kits for kids.",
                'icon'        => $parent['icon'],
                'sort_order'  => $sort,
                'is_active'   => 1,
                'seo_title'   => $parent['name'] . ' | Khoobie Creations',
                'seo_description' => 'Shop ' . strtolower($parent['name']) . ' from Khoobie Creations — handmade craft kits delivered to your door.',
            ]);
            $catId[$parent['slug']] = (int) $db->insertID();
            $sort += 10;

            $cs = 10;
            foreach ($parent['children'] as $child) {
                $db->table('categories')->insert([
                    'parent_id'   => $catId[$parent['slug']],
                    'slug'        => $child['slug'],
                    'name'        => $child['name'],
                    'description' => "Browse our handcrafted {$child['name']} collection.",
                    'sort_order'  => $cs,
                    'is_active'   => 1,
                    'seo_title'   => $child['name'] . ' | ' . $parent['name'] . ' | Khoobie',
                    'seo_description' => "Discover handmade {$child['name']} for kids on Khoobie Creations.",
                ]);
                $catId[$child['slug']] = (int) $db->insertID();
                $cs += 10;
            }
        }

        // ---------- 3. Refs ----------
        $tax12 = $db->table('tax_classes')->where('slug', 'gst-12')->get()->getRow();
        $taxId = $tax12 ? (int) $tax12->id : null;
        $wh = $db->table('warehouses')->where('is_default', 1)->get()->getRow();
        $whId = $wh ? (int) $wh->id : null;

        // ---------- 4. Catalogue ----------
        $catalog = $this->productCatalog();
        $videos  = $this->demoVideos();

        $productCounter = 1;
        foreach ($catalog as $leafSlug => $info) {
            [$pMin, $pMax, $aMin, $aMax, $hsn, $names] = [
                $info['price_min'], $info['price_max'],
                $info['age_min'],   $info['age_max'],
                $info['hsn'],       $info['names'],
            ];
            $parentSlug = $this->parentOf($tree, $leafSlug);

            foreach ($names as $idx => $name) {
                $slug = url_title(strtolower($name), '-', true);
                $sku  = 'KK-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $leafSlug), 0, 3)) . '-' . str_pad((string) $productCounter, 4, '0', STR_PAD_LEFT);

                // Mirror khoobie.com pricing: rupees rounded to nearest ₹49/99 with 20-40% discount
                $sale    = (int) (random_int($pMin, $pMax) * 100);
                $sale    = (int) (round($sale / 5000) * 5000) ?: $sale;
                $compare = (int) ($sale * (1 + (random_int(20, 40) / 100)));
                $compare = (int) (round($compare / 5000) * 5000) ?: $compare;

                $rating      = round(4.2 + (mt_rand() / mt_getrandmax()) * 0.7, 1);
                $ratingCount = random_int(12, 320);
                $salesCount  = random_int(8, 600);

                $heroImage = "https://picsum.photos/seed/{$slug}/900/900";
                // 4 gallery images = 5 product images total (hero + 4 thumbnails)
                $gallery = [
                    "https://picsum.photos/seed/{$slug}-a/900/900",
                    "https://picsum.photos/seed/{$slug}-b/900/900",
                    "https://picsum.photos/seed/{$slug}-c/900/900",
                    "https://picsum.photos/seed/{$slug}-d/900/900",
                ];
                // Every product gets a demo video URL (rotated across the pool)
                $videoUrl = $videos[$productCounter % count($videos)];
                $isFeatured = ($idx < 2) ? 1 : 0;

                $shortDesc = $this->shortDesc($leafSlug, $name);
                $longDesc  = $this->longDesc($leafSlug, $name, $aMin, $aMax);
                $richBlocks = $this->richBlocks($name, $heroImage, $videoUrl);

                $db->table('products')->insert([
                    'sku'           => $sku,
                    'slug'          => $slug,
                    'name'          => $name,
                    'type'          => 'simple',
                    'short_desc'    => $shortDesc,
                    'long_desc'     => $longDesc,
                    'hero_image'    => $heroImage,
                    'gallery'       => json_encode($gallery),
                    'video_url'     => $videoUrl,
                    'status'        => 'active',
                    'is_featured'   => $isFeatured,
                    'tax_class_id'  => $taxId,
                    'hsn_code'      => $hsn,
                    'age_min_years' => $aMin,
                    'age_max_years' => $aMax,
                    'rating_avg'    => $rating,
                    'rating_count'  => $ratingCount,
                    'sales_count'   => $salesCount,
                    'seo_title'     => $name . ' | Khoobie Creations',
                    'seo_description' => substr($shortDesc, 0, 160),
                    'rich_blocks'   => json_encode($richBlocks),
                    'published_at'  => date('Y-m-d H:i:s'),
                ]);
                $productId = (int) $db->insertID();

                $db->table('product_categories')->insert(['product_id' => $productId, 'category_id' => $catId[$leafSlug], 'sort_order' => $idx]);
                if ($parentSlug && isset($catId[$parentSlug])) {
                    $db->table('product_categories')->insert(['product_id' => $productId, 'category_id' => $catId[$parentSlug], 'sort_order' => $idx]);
                }

                $db->table('product_variants')->insert([
                    'product_id'       => $productId,
                    'sku'              => $sku . '-V1',
                    'name'             => 'Default',
                    'price'            => $sale,
                    'compare_at_price' => $compare,
                    'cost'             => (int) ($sale * 0.5),
                    'weight_g'         => random_int(150, 1200),
                    'is_default'       => 1,
                    'is_active'        => 1,
                ]);
                $variantId = (int) $db->insertID();

                if ($whId) {
                    $db->table('inventory')->insert([
                        'variant_id'   => $variantId,
                        'warehouse_id' => $whId,
                        'qty_on_hand'  => random_int(5, 150),
                        'reorder_level'=> 10,
                    ]);
                }

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

        // ---------- 5. Related products within same leaf ----------
        $rows = $db->table('product_categories pc')
            ->join('categories c', 'c.id = pc.category_id')
            ->select('pc.product_id, c.slug')
            ->where('c.parent_id IS NOT NULL')
            ->get()->getResultArray();
        $byLeaf = [];
        foreach ($rows as $r) $byLeaf[$r['slug']][] = (int) $r['product_id'];
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

    // -------------------- helpers --------------------

    private function parentOf(array $tree, string $childSlug): ?string
    {
        foreach ($tree as $p) {
            foreach ($p['children'] as $c) if ($c['slug'] === $childSlug) return $p['slug'];
        }
        return null;
    }

    private function demoVideos(): array
    {
        return [
            'https://www.youtube.com/embed/M7lc1UVf-VE',
            'https://www.youtube.com/embed/aqz-KE-bpKQ',
            'https://www.youtube.com/embed/ScMzIvxBSi4',
            'https://www.youtube.com/embed/jNQXAC9IVRw',
        ];
    }

    private function shortDesc(string $cat, string $name): string
    {
        return match ($cat) {
            'diy-paint-kits'        => 'A complete DIY paint kit with wooden cutouts, paints, brushes & palette — everything your little one needs to create their masterpiece.',
            'wooden-story-kits'     => 'Introduce kids to Indian mythology and stories through hands-on, screen-free creative painting.',
            'art-craft-supplies'    => 'Premium art & craft supplies hand-picked for safe, mess-friendly creativity at home.',
            'garden-grow-kits'      => 'Plant, water, watch grow. A complete grow-your-own kit with seeds, soil pellets, pots and a kid-friendly guide.',
            'edible-garden-kits'    => 'Grow your own salad bowl — kid-safe seeds with everything needed to harvest fresh greens at home.',
            'nature-explorer-kits'  => 'Get curious about the outdoors with this nature-explorer kit — hands-on, screen-free, full of "wow" moments.',
            'pencil-cases-pouches'  => 'Cute, sturdy and roomy — a pouch that holds it all, and that your kid will actually love using.',
            'personalized-items'    => 'Personalised with your child\'s name — a special little gift they will treasure.',
            'school-stationery'     => 'Quality school stationery that lasts longer than the term.',
            'mini-paint-kits'       => 'A perfectly-sized return-gift paint kit — bring home memories from every birthday party.',
            'party-favor-packs'     => 'Crowd-pleasing party favours kids actually use long after the party ends.',
            'bulk-gift-combos'      => 'Pre-curated combo packs for return gifts, in bulk-friendly pricing.',
            default                 => 'A handmade Khoobie creation, made to delight curious kids and proud parents.',
        };
    }

    private function longDesc(string $cat, string $name, int $aMin, int $aMax): string
    {
        $p1 = "{$name} is a Khoobie Creations original — designed by our craft team in Noida and hand-checked for quality before it leaves the workshop.";
        $p2 = "Recommended for ages {$aMin}–{$aMax}, it brings the joy of a hands-on craft session into your living room — no screens needed.";
        $p3 = "Every kit includes clear, kid-friendly instructions and all the supplies needed to finish the project. Adult supervision is recommended for the youngest crafters, but most kids will pick it up on their own.";
        $p4 = "Made with non-toxic, child-safe materials. Beautifully packaged — perfect for gifting too.";
        return implode("\n\n", [$p1, $p2, $p3, $p4]);
    }

    private function richBlocks(string $name, string $hero, ?string $video): array
    {
        $blocks = [
            ['type' => 'usp_grid', 'items' => [
                ['icon' => '🎨', 'title' => 'Complete kit',       'desc' => 'Everything included — no extra trips to the store'],
                ['icon' => '🛡️', 'title' => 'Child-safe',        'desc' => 'Non-toxic materials, kid-tested by our team'],
                ['icon' => '📦', 'title' => 'Free shipping ₹999+','desc' => 'Pan-India delivery in 2–6 business days'],
                ['icon' => '↩️', 'title' => '7-day returns',     'desc' => 'No questions asked'],
            ]],
        ];
        if ($video) {
            $blocks[] = ['type' => 'video', 'url' => $video, 'caption' => "See {$name} in action"];
        }
        $blocks[] = ['type' => 'faq', 'items' => [
            ['q' => 'Is this suitable as a return gift?', 'a' => 'Yes! Many of our kits are popular return-gift picks. Bulk pricing is available — drop us a WhatsApp at +91 88992 23300.'],
            ['q' => 'How long does shipping take?',       'a' => '2–6 business days across India. Tracking shared via WhatsApp + email once dispatched.'],
            ['q' => 'Can I return it?',                   'a' => 'Yes — 7-day no-questions-asked returns from delivery date for unused items.'],
            ['q' => 'Do you ship pan-India?',             'a' => 'Yes, we ship to every pincode in India. COD is available on most pincodes.'],
        ]];
        return $blocks;
    }

    private function productAttributes(string $cat): array
    {
        $base = [
            ['group' => 'features', 'key' => 'Handmade',           'value' => 'Yes',     'filter' => 1],
            ['group' => 'features', 'key' => 'Country of origin',  'value' => 'India',   'filter' => 0],
            ['group' => 'features', 'key' => 'Screen-free',        'value' => 'Yes',     'filter' => 1],
            ['group' => 'shipping', 'key' => 'Ships in',           'value' => '24 hours','filter' => 0],
        ];
        $per = [
            'diy-paint-kits'       => [['group'=>'specs','key'=>'Includes','value'=>'Wooden cutout + paints + brushes + palette','filter'=>0]],
            'wooden-story-kits'    => [['group'=>'specs','key'=>'Theme','value'=>'Indian mythology / stories','filter'=>1]],
            'art-craft-supplies'   => [['group'=>'specs','key'=>'Material','value'=>'Non-toxic, child-safe','filter'=>0]],
            'garden-grow-kits'     => [['group'=>'specs','key'=>'Includes','value'=>'Seeds + soil pellet + pot + guide','filter'=>0]],
            'edible-garden-kits'   => [['group'=>'specs','key'=>'Includes','value'=>'Edible seeds + grow kit','filter'=>0]],
            'nature-explorer-kits' => [['group'=>'specs','key'=>'Focus','value'=>'Outdoor / nature exploration','filter'=>1]],
            'pencil-cases-pouches' => [['group'=>'specs','key'=>'Material','value'=>'EVA / fabric','filter'=>1]],
            'personalized-items'   => [['group'=>'specs','key'=>'Personalised','value'=>'Yes — share name at checkout','filter'=>1]],
            'school-stationery'    => [['group'=>'specs','key'=>'Use','value'=>'School + home','filter'=>0]],
            'mini-paint-kits'      => [['group'=>'specs','key'=>'Best for','value'=>'Return gifts','filter'=>1]],
            'party-favor-packs'    => [['group'=>'specs','key'=>'Pack of','value'=>'10 / 20 / 30','filter'=>1]],
            'bulk-gift-combos'     => [['group'=>'specs','key'=>'Pack of','value'=>'Bulk packs available','filter'=>0]],
        ];
        return array_merge($base, $per[$cat] ?? []);
    }

    /** 12 leaf categories × 12 products each = 144 total. Names mirror khoobie.com style. */
    private function productCatalog(): array
    {
        return [
            'diy-paint-kits' => [
                'price_min' => 399, 'price_max' => 799, 'age_min' => 4, 'age_max' => 12, 'hsn' => '9503',
                'names' => [
                    'DIY Painting Art Kit Animals Wooden Cutouts',
                    'DIY Paint Kit Rainbow Wooden Cutouts',
                    'DIY Paint Kit Sea Animals Painting Kit',
                    'DIY Paint Kit Space Astronaut Painting Kit',
                    'DIY Paint Kit Wooden Door Sign Painting Kit',
                    'DIY Paint Kit Wooden Mandala Painting Kit',
                    'DIY Paint Kit Birds of India Wooden Cutouts',
                    'DIY Paint Kit Dinosaur Wooden Cutouts',
                    'DIY Paint Kit Vehicles Wooden Cutouts',
                    'DIY Paint Kit Garden Insects Wooden Cutouts',
                    'DIY Paint Kit Festival Themes Wooden Set',
                    'DIY Paint Kit Wooden Photo Frame Painting',
                ],
            ],
            'wooden-story-kits' => [
                'price_min' => 499, 'price_max' => 999, 'age_min' => 5, 'age_max' => 12, 'hsn' => '9503',
                'names' => [
                    'DIY Paint Kit Krishna Wall Hanging',
                    'DIY Paint Kit Wooden Ramayana Painting Kit',
                    'DIY Paint Kit Ganesha Wall Hanging',
                    'DIY Paint Kit Diwali Diya Painting Set',
                    'DIY Paint Kit Mahabharata Story Painting Kit',
                    'DIY Paint Kit Indian Festivals Wooden Set',
                    'DIY Paint Kit Hanuman Wall Hanging',
                    'DIY Paint Kit Krishna and Cow Wooden Set',
                    'DIY Paint Kit Saraswati Wall Hanging',
                    'DIY Paint Kit Holi Festival Wooden Cutouts',
                    'DIY Paint Kit Navratri Story Painting Kit',
                    'DIY Paint Kit Raksha Bandhan Rakhi Painting Kit',
                ],
            ],
            'art-craft-supplies' => [
                'price_min' => 199, 'price_max' => 899, 'age_min' => 4, 'age_max' => 14, 'hsn' => '9503',
                'names' => [
                    'Air Dry Clay Starter Set (12 Colours)',
                    'Pot Painting Candle Making Art and Craft Kit',
                    'Watercolour Paint Pan Set with Brushes',
                    'Acrylic Paint Bottles Mega Pack',
                    'Modeling Clay Reusable Set',
                    'Glitter Glue Pens Pack',
                    'Origami Paper 100-Sheet Pack',
                    'Khoobie Craft Glue Stick Combo',
                    'Wooden Craft Sticks 200-Piece Pack',
                    'Pipe Cleaners Assorted Colours Pack',
                    'Khoobie Craft Beads Storage Box',
                    'Khoobie Mini Paint Brush Set (12 Pieces)',
                ],
            ],
            'garden-grow-kits' => [
                'price_min' => 299, 'price_max' => 899, 'age_min' => 4, 'age_max' => 12, 'hsn' => '0601',
                'names' => [
                    'Marigold Grow Kit for Kids',
                    'Sunflower Grow Kit for Kids',
                    'Tulsi & Mint Grow Kit',
                    'Carrot Grow Kit for Kids',
                    'Strawberry Grow Kit for Kids',
                    'Cosmos Flower Grow Kit',
                    'Zinnia Grow Kit for Kids',
                    'Sweet Pea Grow Kit',
                    'Aloe Vera Mini Grow Kit',
                    'Hibiscus Grow Kit for Beginners',
                    'Lavender Grow Kit for Kids',
                    'Pumpkin Grow Kit for Kids',
                ],
            ],
            'edible-garden-kits' => [
                'price_min' => 349, 'price_max' => 899, 'age_min' => 5, 'age_max' => 12, 'hsn' => '0601',
                'names' => [
                    '3 in 1 Gourmet Salad Kit (Vegetable Seeds)',
                    '3 in 1 Salad Kit with Red Cherry Tomato Seeds',
                    'Microgreens Grow Kit for Kids',
                    'Herb Garden Starter Kit',
                    'Cherry Tomato Grow Kit',
                    'Lettuce Grow Kit for Kids',
                    'Coriander and Methi Grow Kit',
                    'Sprouts Grow Kit Family Pack',
                    'Spinach and Palak Grow Kit',
                    'Bell Pepper Grow Kit for Kids',
                    'Chilli Pepper Grow Kit',
                    'Basil & Mint Edible Garden Combo',
                ],
            ],
            'nature-explorer-kits' => [
                'price_min' => 449, 'price_max' => 1299, 'age_min' => 5, 'age_max' => 14, 'hsn' => '9503',
                'names' => [
                    'Bug Catcher Nature Explorer Kit',
                    'Bird Watcher Junior Explorer Kit',
                    'Leaf Pressing Nature Craft Kit',
                    'Pebble Painting Nature Craft Kit',
                    'Mini Magnifying Glass Explorer Kit',
                    'Butterfly Garden Observation Kit',
                    'Khoobie Mini Compass Adventure Kit',
                    'Junior Botanist Pressed Flower Kit',
                    'Nature Journal & Field Kit',
                    'Mini Telescope Star Watcher Kit',
                    'Khoobie Outdoor Treasure Hunt Kit',
                    'Junior Geologist Rock Collection Kit',
                ],
            ],
            'pencil-cases-pouches' => [
                'price_min' => 299, 'price_max' => 999, 'age_min' => 3, 'age_max' => 14, 'hsn' => '4202',
                'names' => [
                    'Pencil Case EVA Cartoon Storage Pouch',
                    'Khoobie Unicorn Pencil Pouch',
                    'Dinosaur Print Pencil Case',
                    'Space Astronaut Pencil Pouch',
                    'Khoobie Princess Pencil Pouch',
                    'Animal Friends Pencil Case',
                    'Khoobie Super Hero Pencil Pouch',
                    'Mandala Art Pencil Case',
                    'Khoobie Rainbow Stripes Pencil Pouch',
                    'Mermaid Print Pencil Pouch',
                    'Race Car Print Pencil Case',
                    'Khoobie Floral Pencil Pouch',
                ],
            ],
            'personalized-items' => [
                'price_min' => 299, 'price_max' => 899, 'age_min' => 3, 'age_max' => 14, 'hsn' => '9503',
                'names' => [
                    'Personalized Bamboo Brush',
                    'Personalized Wooden Door Sign',
                    'Personalized Pencil Box with Name',
                    'Personalized Khoobie Tote Bag',
                    'Personalized Wall Hanging with Name',
                    'Personalized Water Bottle with Name',
                    'Personalized Khoobie Apron for Kids',
                    'Personalized Wooden Name Puzzle',
                    'Personalized Khoobie Lunch Box',
                    'Personalized Khoobie Notebook',
                    'Personalized Mug for Kids',
                    'Personalized Khoobie Backpack Tag',
                ],
            ],
            'school-stationery' => [
                'price_min' => 199, 'price_max' => 799, 'age_min' => 4, 'age_max' => 14, 'hsn' => '4820',
                'names' => [
                    'Khoobie Spiral Notebook Set of 4',
                    'Khoobie Sketchbook A4 100 GSM',
                    'Wax Crayons 24-Colour Pack',
                    'Khoobie Sharpener and Eraser Combo',
                    'Khoobie Glue Stick 3-Pack',
                    'Khoobie Stickers Mega Sheet Pack',
                    'Coloured Pencils 24-Shade Set',
                    'Khoobie Doodle Marker 12-Pack',
                    'Khoobie Geometry Box Combo',
                    'Khoobie Highlighters 6-Pack',
                    'Khoobie Coloured Sticky Notes Pack',
                    'Khoobie Cute Erasers Collection',
                ],
            ],
            'mini-paint-kits' => [
                'price_min' => 99, 'price_max' => 399, 'age_min' => 4, 'age_max' => 12, 'hsn' => '9503',
                'names' => [
                    'Return Gift Mini DIY Paint Kit (Single)',
                    'Return Gift Mini Wooden Cutout Paint Kit',
                    'Mini Mandala Paint Return Gift',
                    'Mini Animal Cutout Paint Return Gift',
                    'Mini Rainbow Paint Kit Return Gift',
                    'Mini Krishna Paint Kit Return Gift',
                    'Mini Sea Animal Paint Kit Return Gift',
                    'Mini Space Paint Kit Return Gift',
                    'Mini Dinosaur Paint Kit Return Gift',
                    'Mini Bird Paint Kit Return Gift',
                    'Mini Festival Paint Kit Return Gift',
                    'Mini Door Sign Paint Kit Return Gift',
                ],
            ],
            'party-favor-packs' => [
                'price_min' => 599, 'price_max' => 1999, 'age_min' => 4, 'age_max' => 12, 'hsn' => '9503',
                'names' => [
                    'Party Favor Pack of 10 — DIY Paint Kits',
                    'Party Favor Pack of 20 — DIY Paint Kits',
                    'Party Favor Pack of 10 — Air Dry Clay',
                    'Party Favor Pack of 10 — Pencil Cases',
                    'Party Favor Pack of 10 — Khoobie Mini Sketchbooks',
                    'Party Favor Pack of 10 — Garden Grow Kits',
                    'Party Favor Pack of 10 — Khoobie Sticker Packs',
                    'Party Favor Pack of 10 — Khoobie Crayon Boxes',
                    'Party Favor Pack of 10 — Khoobie Mandala Mini Kits',
                    'Party Favor Pack of 10 — Khoobie Wooden Toy Mix',
                    'Party Favor Pack of 10 — Khoobie Eco Crayons',
                    'Party Favor Pack of 10 — Khoobie Mini Puzzle Set',
                ],
            ],
            'bulk-gift-combos' => [
                'price_min' => 1499, 'price_max' => 4999, 'age_min' => 4, 'age_max' => 12, 'hsn' => '9503',
                'names' => [
                    'Bulk Combo Pack — Birthday Return Gifts (Pack of 15)',
                    'Bulk Combo Pack — Birthday Return Gifts (Pack of 30)',
                    'Bulk Combo Pack — Festival Return Gifts',
                    'Bulk Combo Pack — School Goodie Bags',
                    'Bulk Combo Pack — Welcome Kid Gift Combo',
                    'Bulk Combo Pack — Toddler Party Favours',
                    'Bulk Combo Pack — Theme Birthday Combo',
                    'Bulk Combo Pack — Pack of 50 Mini Crafts',
                    'Bulk Combo Pack — Corporate Kid Gifting',
                    'Bulk Combo Pack — School Goodbye Memento',
                    'Bulk Combo Pack — Welcome Baby Combo',
                    'Bulk Combo Pack — Khoobie Curator\'s Choice',
                ],
            ],
        ];
    }
}
