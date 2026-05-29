<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $rows = [
            ['slug' => 'board-games', 'name' => 'Board Games',  'description' => 'Family and kids board games for screen-free fun', 'sort_order' => 10, 'is_active' => 1],
            ['slug' => 'books',       'name' => 'Books',        'description' => 'Story books, activity books, learning books',     'sort_order' => 20, 'is_active' => 1],
            ['slug' => 'experiments', 'name' => 'Experiments',  'description' => 'Hands-on STEM and science kits',                   'sort_order' => 30, 'is_active' => 1],
            ['slug' => 'projects',    'name' => 'Project Kits', 'description' => 'Craft, art and DIY project kits',                  'sort_order' => 40, 'is_active' => 1],
            ['slug' => 'booklets',    'name' => 'Booklets',     'description' => 'Quick activity booklets and workbooks',            'sort_order' => 50, 'is_active' => 1],
            ['slug' => 'digital',     'name' => 'Digital',      'description' => 'Printable downloads, e-books, digital activities', 'sort_order' => 60, 'is_active' => 1],
            ['slug' => 'gift-boxes',  'name' => 'Gift Boxes',   'description' => 'Curated screen-free gift boxes',                    'sort_order' => 70, 'is_active' => 1],
            ['slug' => 'classes',     'name' => 'Live Classes', 'description' => 'Workshops and live online classes',                'sort_order' => 80, 'is_active' => 1],
        ];
        $this->db->table('categories')->ignore(true)->insertBatch($rows);
    }
}
