<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $defaults = ['label' => null, 'description' => null, 'is_public' => 0];

        $rows = [
            // Company
            ['group_key' => 'company', 'key' => 'name',              'value' => 'Krafty Khoobie Pvt Ltd',    'value_type' => 'string', 'label' => 'Legal company name',     'is_public' => 1],
            ['group_key' => 'company', 'key' => 'brand',             'value' => 'Krafty Khoobie',            'value_type' => 'string', 'label' => 'Public brand name',      'is_public' => 1],
            ['group_key' => 'company', 'key' => 'gstin',             'value' => '',                          'value_type' => 'string', 'label' => 'GSTIN'],
            ['group_key' => 'company', 'key' => 'pan',               'value' => '',                          'value_type' => 'string', 'label' => 'PAN'],
            ['group_key' => 'company', 'key' => 'registered_address','value' => '',                          'value_type' => 'json',   'label' => 'Registered address'],
            ['group_key' => 'company', 'key' => 'support_email',     'value' => 'hello@khoobie.com',         'value_type' => 'string', 'label' => 'Support email',   'is_public' => 1],
            ['group_key' => 'company', 'key' => 'support_phone',     'value' => '',                          'value_type' => 'string', 'label' => 'Support phone',   'is_public' => 1],
            ['group_key' => 'company', 'key' => 'support_whatsapp',  'value' => '',                          'value_type' => 'string', 'label' => 'WhatsApp number', 'is_public' => 1],

            // Shipping
            ['group_key' => 'shipping', 'key' => 'free_shipping_threshold', 'value' => '99900', 'value_type' => 'int', 'label' => 'Free shipping above (paise)', 'is_public' => 1],
            ['group_key' => 'shipping', 'key' => 'default_charge',          'value' => '7900',  'value_type' => 'int', 'label' => 'Default shipping charge (paise)'],
            ['group_key' => 'shipping', 'key' => 'cod_fee',                 'value' => '4900',  'value_type' => 'int', 'label' => 'COD handling fee (paise)'],

            // COD policy
            ['group_key' => 'cod', 'key' => 'enabled',           'value' => '1',      'value_type' => 'bool', 'label' => 'COD enabled'],
            ['group_key' => 'cod', 'key' => 'partial_enabled',   'value' => '1',      'value_type' => 'bool', 'label' => 'Partial-COD enabled'],
            ['group_key' => 'cod', 'key' => 'min_order',         'value' => '0',      'value_type' => 'int',  'label' => 'Min order for COD (paise)'],
            ['group_key' => 'cod', 'key' => 'max_order',         'value' => '1000000','value_type' => 'int',  'label' => 'Max order for COD (paise)'],
            ['group_key' => 'cod', 'key' => 'advance_min',       'value' => '10000',  'value_type' => 'int',  'label' => 'Min advance for partial COD (paise)'],
            ['group_key' => 'cod', 'key' => 'blocked_pincodes',  'value' => '[]',     'value_type' => 'json', 'label' => 'Pincodes where COD is disabled'],

            // Loyalty
            ['group_key' => 'loyalty', 'key' => 'enabled',           'value' => '1',   'value_type' => 'bool',   'label' => 'Loyalty programme enabled'],
            ['group_key' => 'loyalty', 'key' => 'points_per_rupee',  'value' => '1',   'value_type' => 'int',    'label' => 'Points earned per rupee spent'],
            ['group_key' => 'loyalty', 'key' => 'rupee_per_point',   'value' => '0.25','value_type' => 'string', 'label' => 'Rupee value per redeemed point'],
            ['group_key' => 'loyalty', 'key' => 'min_redeem_points', 'value' => '100', 'value_type' => 'int',    'label' => 'Minimum points to redeem'],
            ['group_key' => 'loyalty', 'key' => 'expiry_days',       'value' => '365', 'value_type' => 'int',    'label' => 'Points expiry (days)'],

            // Reviews
            ['group_key' => 'reviews', 'key' => 'require_purchase',  'value' => '0', 'value_type' => 'bool', 'label' => 'Only verified buyers can review'],
            ['group_key' => 'reviews', 'key' => 'auto_publish',      'value' => '0', 'value_type' => 'bool', 'label' => 'Auto-publish reviews (skip moderation)'],

            // Order confirmation flow
            ['group_key' => 'orders', 'key' => 'require_confirmation_cod',     'value' => '1', 'value_type' => 'bool',   'label' => 'Require phone confirmation for COD orders'],
            ['group_key' => 'orders', 'key' => 'require_confirmation_prepaid', 'value' => '0', 'value_type' => 'bool',   'label' => 'Require phone confirmation for prepaid orders'],
            ['group_key' => 'orders', 'key' => 'auto_cancel_after_hours',      'value' => '48','value_type' => 'int',    'label' => 'Auto-cancel unpaid orders after N hours'],
            ['group_key' => 'orders', 'key' => 'invoice_prefix',               'value' => 'KK','value_type' => 'string', 'label' => 'Invoice number prefix'],

            // Branding (public)
            ['group_key' => 'theme', 'key' => 'primary_color', 'value' => '#FF6F61', 'value_type' => 'string', 'label' => 'Primary brand color', 'is_public' => 1],
            ['group_key' => 'theme', 'key' => 'accent_color',  'value' => '#F9C13C', 'value_type' => 'string', 'label' => 'Accent brand color',  'is_public' => 1],
        ];

        $normalized = array_map(fn ($r) => array_merge($defaults, $r), $rows);
        $this->db->table('settings')->ignore(true)->insertBatch($normalized);
    }
}
