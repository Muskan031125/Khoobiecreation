<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('RoleSeeder');
        $this->call('SettingsSeeder');
        $this->call('TaxClassSeeder');
        $this->call('WarehouseSeeder');
        $this->call('CustomerGroupSeeder');
        $this->call('LoyaltyRulesSeeder');
        $this->call('CategorySeeder');
        $this->call('ProductSeeder');
        $this->call('PromotionSeeder');
        $this->call('PopupSeeder');
        $this->call('BannerSeeder');
        $this->call('AdminUserSeeder');
    }
}
