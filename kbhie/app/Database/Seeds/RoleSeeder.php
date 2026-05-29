<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $rows = [
            ['name' => 'super_admin', 'label' => 'Super Admin', 'permissions' => json_encode(['*'])],
            ['name' => 'admin',       'label' => 'Admin',       'permissions' => json_encode(['catalog.*','orders.*','customers.*','marketing.*','reports.*'])],
            ['name' => 'staff',       'label' => 'Staff',       'permissions' => json_encode(['orders.read','orders.confirm','customers.read'])],
            ['name' => 'partner',     'label' => 'Partner',     'permissions' => json_encode(['partner.*'])],
            ['name' => 'customer',    'label' => 'Customer',    'permissions' => json_encode(['account.*'])],
            ['name' => 'affiliate',   'label' => 'Affiliate',   'permissions' => json_encode(['affiliate.*'])],
        ];
        $this->db->table('roles')->ignore(true)->insertBatch($rows);
    }
}
