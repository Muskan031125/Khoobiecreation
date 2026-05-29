<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('warehouses')->ignore(true)->insert([
            'name'       => 'Khoobie HQ Warehouse',
            'code'       => 'KB-MAIN',
            'type'       => 'own',
            'is_default' => 1,
            'is_active'  => 1,
        ]);
    }
}
