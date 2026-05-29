<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Look up tax class GST 12% for default
        $tax12 = $this->db->table('tax_classes')->where('slug', 'gst-12')->get()->getRow();
        $taxId = $tax12 ? (int) $tax12->id : null;

        $warehouse = $this->db->table('warehouses')->where('is_default', 1)->get()->getRow();
        $whId = $warehouse ? (int) $warehouse->id : null;

        $products = [
            [
                'sku'           => 'KB-BG-001',
                'slug'          => 'word-wizard-board-game',
                'name'          => 'Word Wizard — Spelling Adventure Board Game',
                'type'          => 'simple',
                'short_desc'    => 'A spelling adventure for ages 6+. 2-4 players. Screen-free fun for the whole family.',
                'long_desc'     => 'Roll, race and spell your way across the magical board. Builds vocabulary, spelling and confidence while families bond over the table.',
                'status'        => 'active',
                'is_featured'   => 1,
                'age_min_years' => 6,
                'age_max_years' => 12,
                'category_slug' => 'board-games',
                'price'         => 89900,  // ₹899
                'compare_at'    => 119900,
                'stock'         => 50,
            ],
            [
                'sku'           => 'KB-BK-001',
                'slug'          => 'science-stories-junior',
                'name'          => 'Science Stories Junior — Book Set of 5',
                'type'          => 'simple',
                'short_desc'    => 'Five illustrated science storybooks for curious kids ages 5-9.',
                'long_desc'     => 'Beautifully illustrated stories that introduce big science ideas through small adventures. Perfect bedtime reading for curious kids.',
                'status'        => 'active',
                'is_featured'   => 1,
                'age_min_years' => 5,
                'age_max_years' => 9,
                'category_slug' => 'books',
                'price'         => 64900,
                'compare_at'    => 79900,
                'stock'         => 80,
            ],
            [
                'sku'           => 'KB-EX-001',
                'slug'          => 'volcano-experiment-kit',
                'name'          => 'Erupting Volcano Experiment Kit',
                'type'          => 'simple',
                'short_desc'    => 'Build and erupt your own volcano! Hands-on science for ages 8+.',
                'long_desc'     => 'Complete kit with everything needed for safe, spectacular volcano experiments. Includes instruction booklet and 10 experiment cards.',
                'status'        => 'active',
                'is_featured'   => 1,
                'age_min_years' => 8,
                'age_max_years' => 14,
                'category_slug' => 'experiments',
                'price'         => 129900,
                'compare_at'    => 159900,
                'stock'         => 30,
            ],
            [
                'sku'           => 'KB-DG-001',
                'slug'          => 'screen-free-week-printable-pack',
                'name'          => '7-Day Screen-Free Activity Pack (Digital)',
                'type'          => 'digital',
                'short_desc'    => 'Printable activity pack — 7 days, 21 activities, hours of off-screen fun.',
                'long_desc'     => 'A curated week of printable challenges, puzzles, art prompts and family games. Instant download.',
                'status'        => 'active',
                'is_featured'   => 0,
                'age_min_years' => 4,
                'age_max_years' => 12,
                'category_slug' => 'digital',
                'price'         => 19900,
                'compare_at'    => null,
                'stock'         => null, // digital — unlimited
            ],
        ];

        foreach ($products as $p) {
            // Idempotent: skip if already seeded
            if ($this->db->table('products')->where('sku', $p['sku'])->countAllResults() > 0) {
                continue;
            }
            $cat = $this->db->table('categories')->where('slug', $p['category_slug'])->get()->getRow();
            if (! $cat) continue;

            $this->db->table('products')->insert([
                'sku'           => $p['sku'],
                'slug'          => $p['slug'],
                'name'          => $p['name'],
                'type'          => $p['type'],
                'short_desc'    => $p['short_desc'],
                'long_desc'     => $p['long_desc'],
                'status'        => $p['status'],
                'is_featured'   => $p['is_featured'],
                'tax_class_id'  => $taxId,
                'hsn_code'      => '9503',
                'age_min_years' => $p['age_min_years'],
                'age_max_years' => $p['age_max_years'],
                'published_at'  => date('Y-m-d H:i:s'),
            ]);
            $productId = (int) $this->db->insertID();

            $this->db->table('product_categories')->insert([
                'product_id'  => $productId,
                'category_id' => $cat->id,
            ]);

            // Default variant
            $this->db->table('product_variants')->insert([
                'product_id'       => $productId,
                'sku'              => $p['sku'] . '-DEFAULT',
                'name'             => 'Default',
                'price'            => $p['price'],
                'compare_at_price' => $p['compare_at'],
                'is_default'       => 1,
                'is_active'        => 1,
            ]);
            $variantId = (int) $this->db->insertID();

            // Inventory (skip digital)
            if ($p['stock'] !== null && $whId) {
                $this->db->table('inventory')->insert([
                    'variant_id'   => $variantId,
                    'warehouse_id' => $whId,
                    'qty_on_hand'  => $p['stock'],
                ]);
            }

            // Digital asset placeholder for digital products
            if ($p['type'] === 'digital') {
                $this->db->table('digital_assets')->insert([
                    'product_id'   => $productId,
                    'variant_id'   => $variantId,
                    'name'         => $p['name'] . ' — Download',
                    'file_path'    => 'digital/placeholder.pdf',
                    'license_type' => 'personal',
                    'expiry_days'  => 90,
                ]);
            }
        }
    }
}
