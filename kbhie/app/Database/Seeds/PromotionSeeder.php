<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('coupons')->where('code', 'WELCOME10')->countAllResults() > 0) {
            return; // already seeded
        }

        // 1. Welcome 10% off (coupon WELCOME10)
        $this->db->table('promotions')->insert([
            'name'             => 'Welcome 10% Off',
            'description'      => '10% off for first-time buyers',
            'type'             => 'percent_off',
            'scope'            => 'cart',
            'priority'         => 100,
            'rules'            => json_encode(['min_cart' => 49900, 'first_order_only' => true]),
            'rewards'          => json_encode(['type' => 'percent_off', 'value' => 10, 'max_discount' => 30000]),
            'stackable'        => 0,
            'auto_apply'       => 0,
            'requires_coupon'  => 1,
            'is_active'        => 1,
            'show_in_widget'   => 1,
            'banner_text'      => 'New here? Use code WELCOME10 for 10% off your first order.',
        ]);
        $promo1 = (int) $this->db->insertID();
        $this->db->table('coupons')->insert([
            'code'              => 'WELCOME10',
            'promotion_id'      => $promo1,
            'max_uses_per_user' => 1,
            'is_active'         => 1,
        ]);

        // 2. Free shipping over ₹999 (auto)
        $this->db->table('promotions')->insert([
            'name'             => 'Free Shipping Over ₹999',
            'type'             => 'free_shipping',
            'scope'            => 'shipping',
            'priority'         => 50,
            'rules'            => json_encode(['min_cart' => 99900]),
            'rewards'          => json_encode(['type' => 'free_shipping']),
            'stackable'        => 1,
            'auto_apply'       => 1,
            'requires_coupon'  => 0,
            'is_active'        => 1,
            'show_in_widget'   => 1,
            'banner_text'      => 'FREE shipping on orders over ₹999',
        ]);

        // 3. BOGO — Buy 2 board games, get 1 booklet free
        $this->db->table('promotions')->insert([
            'name'             => 'Buy 2 Board Games, Get 1 Booklet Free',
            'type'             => 'bogo',
            'scope'            => 'product',
            'priority'         => 80,
            'rules'            => json_encode([
                'buy' => ['category_slug' => 'board-games', 'min_qty' => 2],
                'get' => ['category_slug' => 'booklets', 'qty' => 1],
            ]),
            'rewards'          => json_encode(['type' => 'free_product', 'from' => 'rule.get']),
            'stackable'        => 0,
            'auto_apply'       => 1,
            'requires_coupon'  => 0,
            'is_active'        => 1,
            'show_in_widget'   => 1,
            'banner_text'      => 'Buy 2 Board Games — get a free Booklet!',
        ]);

        // 4. Festive — flat ₹100 off above ₹1500 (FESTIVE100)
        $this->db->table('promotions')->insert([
            'name'             => 'Festive ₹100 Off',
            'type'             => 'flat_off',
            'scope'            => 'cart',
            'priority'         => 90,
            'rules'            => json_encode(['min_cart' => 150000]),
            'rewards'          => json_encode(['type' => 'flat_off', 'value' => 10000]),
            'stackable'        => 0,
            'auto_apply'       => 0,
            'requires_coupon'  => 1,
            'is_active'        => 1,
        ]);
        $promo4 = (int) $this->db->insertID();
        $this->db->table('coupons')->insert([
            'code'         => 'FESTIVE100',
            'promotion_id' => $promo4,
            'is_active'    => 1,
        ]);
    }
}
