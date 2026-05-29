<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('banners')->countAllResults() > 0) {
            return;
        }
        $rows = [
            ['text' => 'FREE shipping on orders above ₹999 • COD available across India', 'placement' => 'top_bar', 'priority' => 10, 'is_active' => 1],
            ['text' => 'New here? Use code WELCOME10 for 10% off',                       'placement' => 'top_bar', 'priority' => 20, 'is_active' => 1],
        ];
        $this->db->table('banners')->insertBatch($rows);
    }
}
