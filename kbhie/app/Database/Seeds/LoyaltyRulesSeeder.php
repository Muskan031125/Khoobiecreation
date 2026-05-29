<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LoyaltyRulesSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('loyalty_rules')->countAllResults() > 0) return;
        $rows = [
            ['event' => 'signup',    'description' => 'Welcome bonus on signup',       'points_formula' => '100',         'expires_days' => 365, 'is_active' => 1],
            ['event' => 'purchase',  'description' => '1 point per rupee spent',       'points_formula' => 'amount/100',  'expires_days' => 365, 'is_active' => 1],
            ['event' => 'review',    'description' => '50 points per published review','points_formula' => '50',          'expires_days' => 365, 'is_active' => 1],
            ['event' => 'referral',  'description' => '200 points per successful referral', 'points_formula' => '200',    'expires_days' => 365, 'is_active' => 1],
            ['event' => 'birthday',  'description' => 'Birthday bonus',                'points_formula' => '150',         'expires_days' => 90,  'is_active' => 1],
        ];
        $this->db->table('loyalty_rules')->ignore(true)->insertBatch($rows);
    }
}
