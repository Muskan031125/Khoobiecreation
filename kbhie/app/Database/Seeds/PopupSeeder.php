<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PopupSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('popups')->countAllResults() > 0) {
            return;
        }
        $welcomeCoupon = $this->db->table('coupons')->where('code', 'WELCOME10')->get()->getRow();

        $rows = [
            [
                'name'            => 'Welcome — first visit popup',
                'trigger'         => 'time_delay',
                'trigger_value'   => 8,
                'url_pattern'     => '*',
                'frequency_days'  => 7,
                'title'           => 'Welcome, Parent! 🎁',
                'subtitle'        => 'Get 10% off your first order + a chance to win our weekly Goodie Box.',
                'cta_text'        => 'Claim my discount',
                'reward_type'     => 'coupon',
                'reward_coupon_id'=> $welcomeCoupon?->id,
                'reward_message'  => 'Use code WELCOME10 at checkout.',
                'capture_fields'  => json_encode(['name','email','phone']),
                'is_active'       => 1,
            ],
            [
                'name'            => 'Exit intent — don\'t leave empty handed',
                'trigger'         => 'exit_intent',
                'trigger_value'   => 0,
                'url_pattern'     => '*',
                'frequency_days'  => 3,
                'title'           => 'Before you go…',
                'subtitle'        => 'Drop your email and we\'ll send you 10% off + a free printable activity pack.',
                'cta_text'        => 'Send me my freebie',
                'reward_type'     => 'coupon',
                'reward_coupon_id'=> $welcomeCoupon?->id,
                'reward_message'  => null,
                'capture_fields'  => json_encode(['email']),
                'is_active'       => 1,
            ],
        ];
        $this->db->table('popups')->insertBatch($rows);
    }
}
