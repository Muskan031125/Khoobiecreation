<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    public function run()
    {
        $rows = [
            ['name' => 'Retail',     'slug' => 'retail',     'default_discount_pct' => 0,  'requires_approval' => 0, 'is_active' => 1],
            ['name' => 'Wholesale',  'slug' => 'wholesale',  'default_discount_pct' => 15, 'requires_approval' => 1, 'is_active' => 1],
            ['name' => 'Educators',  'slug' => 'educators',  'default_discount_pct' => 10, 'requires_approval' => 1, 'is_active' => 1],
            ['name' => 'Schools',    'slug' => 'schools',    'default_discount_pct' => 20, 'requires_approval' => 1, 'is_active' => 1],
            ['name' => 'Corporate',  'slug' => 'corporate',  'default_discount_pct' => 10, 'requires_approval' => 1, 'is_active' => 1],
        ];
        $this->db->table('customer_groups')->ignore(true)->insertBatch($rows);
    }
}
