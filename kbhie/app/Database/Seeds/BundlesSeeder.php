<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Idempotent demo bundle seeder. Picks one kit + one matching class per topic,
 * creates a bundle priced 20% off the sum-of-parts.
 */
class BundlesSeeder extends Seeder
{
    public function run()
    {
        $combos = [
            ['slug' => 'pottery-kit-and-class',       'name' => 'Pottery Starter Bundle',  'tagline' => 'Clay kit at home + 4-week online pottery class',                'kit_like' => 'clay',      'class_like' => 'pottery'],
            ['slug' => 'paint-kit-and-mandala-course','name' => 'Mandala Mastery Combo',   'tagline' => 'Paint kit + recorded mandala course — perfect rainy-day combo','kit_like' => 'paint',     'class_like' => 'mandala'],
            ['slug' => 'chess-bundle',                'name' => 'Chess Champion Bundle',   'tagline' => 'Wooden chess set + 1-month coaching with FIDE coach',          'kit_like' => 'chess',     'class_like' => 'chess'],
            ['slug' => 'garden-kit-and-meetup',       'name' => 'Garden Explorer Bundle',  'tagline' => 'Grow kit + weekend nature meetup',                              'kit_like' => 'grow',      'class_like' => 'meetup'],
            ['slug' => 'return-gift-bundle',          'name' => 'Birthday Party Bundle',   'tagline' => 'Bulk return gifts + a hosted parent-child meetup',              'kit_like' => 'return',    'class_like' => 'pottery'],
        ];

        $created = 0;
        foreach ($combos as $combo) {
            if ($this->db->table('bundles')->where('slug', $combo['slug'])->countAllResults() > 0) continue;

            $kit = $this->db->table('products')->where('status','active')->whereIn('type', ['simple','variable','bundle'])->like('name', trim($combo['kit_like'], '%'))->limit(1)->get()->getRow();
            $cls = $this->db->table('products')->where('status','active')->whereIn('type', ['course','tuition','meetup'])->like('name', trim($combo['class_like'], '%'))->limit(1)->get()->getRow();
            if (! $kit || ! $cls) continue;

            $kitVar = $this->db->table('product_variants')->where('product_id', $kit->id)->where('is_default', 1)->get()->getRow();
            $clsVar = $this->db->table('product_variants')->where('product_id', $cls->id)->where('is_default', 1)->get()->getRow();
            if (! $kitVar || ! $clsVar) continue;

            $sum   = (int) $kitVar->price + (int) $clsVar->price;
            $price = (int) round($sum * 0.8);  // 20% bundle discount

            $this->db->table('bundles')->insert([
                'slug'         => $combo['slug'],
                'name'         => $combo['name'],
                'tagline'      => $combo['tagline'],
                'description'  => "An expert-curated bundle that pairs a hands-on Khoobie kit with the matching online learning experience.\n\nYour child gets the materials AND the guidance — and you save 20% vs. buying separately.",
                'hero_image'   => $kit->hero_image,
                'bundle_price' => $price,
                'items_total'  => $sum,
                'savings'      => $sum - $price,
                'is_active'    => 1,
                'sort_order'   => 10,
            ]);
            $bid = (int) $this->db->insertID();

            $this->db->table('bundle_items')->insertBatch([
                ['bundle_id'=>$bid, 'product_id'=>$kit->id, 'variant_id'=>$kitVar->id, 'qty'=>1, 'role'=>'anchor', 'sort_order'=>10],
                ['bundle_id'=>$bid, 'product_id'=>$cls->id, 'variant_id'=>$clsVar->id, 'qty'=>1, 'role'=>'add_on', 'sort_order'=>20],
            ]);
            $created++;
        }

        echo "Bundles seeded: {$created} new (out of " . count($combos) . " combos).\n";
    }
}
