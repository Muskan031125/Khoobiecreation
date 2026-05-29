<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TaxClassSeeder extends Seeder
{
    public function run()
    {
        // Indian GST slabs commonly applicable to toys, books, kits
        $classes = [
            ['name' => 'GST 0% (Exempt)', 'slug' => 'gst-0',  'rate_pct' => 0,    'is_inclusive' => 1, 'is_active' => 1],
            ['name' => 'GST 5%',          'slug' => 'gst-5',  'rate_pct' => 5,    'is_inclusive' => 1, 'is_active' => 1],
            ['name' => 'GST 12%',         'slug' => 'gst-12', 'rate_pct' => 12,   'is_inclusive' => 1, 'is_active' => 1],
            ['name' => 'GST 18%',         'slug' => 'gst-18', 'rate_pct' => 18,   'is_inclusive' => 1, 'is_active' => 1],
            ['name' => 'GST 28%',         'slug' => 'gst-28', 'rate_pct' => 28,   'is_inclusive' => 1, 'is_active' => 1],
        ];
        $this->db->table('tax_classes')->ignore(true)->insertBatch($classes);

        // Default tax rates (intrastate = CGST+SGST half each; interstate = IGST)
        if ($this->db->table('tax_rates')->countAllResults() > 0) return;
        $taxClasses = $this->db->table('tax_classes')->get()->getResultArray();
        $rates = [];
        foreach ($taxClasses as $c) {
            $rate = (float) $c['rate_pct'];
            $rates[] = [
                'tax_class_id' => $c['id'],
                'state_code'   => null, // default intra-state
                'cgst_rate'    => $rate / 2,
                'sgst_rate'    => $rate / 2,
                'igst_rate'    => 0,
                'cess_rate'    => 0,
                'is_active'    => 1,
            ];
            $rates[] = [
                'tax_class_id' => $c['id'],
                'state_code'   => 'IGST', // sentinel for any interstate
                'cgst_rate'    => 0,
                'sgst_rate'    => 0,
                'igst_rate'    => $rate,
                'cess_rate'    => 0,
                'is_active'    => 1,
            ];
        }
        if ($rates) $this->db->table('tax_rates')->insertBatch($rates);
    }
}
