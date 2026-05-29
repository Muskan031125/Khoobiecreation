<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Bundles — explicit kit + class combos.
 * "Pottery starter kit (₹600) + 4-week pottery class (₹2,400) = ₹2,500 combo (save ₹500)"
 * Surfaced on relevant PDPs to make the flywheel real.
 */
class CreateBundlesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 150, 'unique' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'tagline'         => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'hero_image'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'bundle_price'    => ['type' => 'INT', 'unsigned' => true, 'comment' => 'In paise'],
            'items_total'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'Sum of item prices (cached)'],
            'savings'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'items_total - bundle_price'],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'      => ['type' => 'INT', 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['is_active','sort_order']);
        $this->forge->createTable('bundles', true, ['ENGINE' => 'InnoDB']);

        // Bundle ↔ Product (with the product's specific variant)
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'bundle_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'qty'          => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'role'         => ['type' => 'ENUM', 'constraint' => ['anchor','add_on','bonus'], 'default' => 'anchor', 'comment' => 'anchor = main item; add_on = upsell; bonus = free'],
            'sort_order'   => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['bundle_id','sort_order']);
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('bundle_id', 'bundles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bundle_items', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('bundle_items', true);
        $this->forge->dropTable('bundles', true);
    }
}
