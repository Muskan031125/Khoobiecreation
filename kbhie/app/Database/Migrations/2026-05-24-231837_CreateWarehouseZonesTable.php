<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Maps pincode prefixes to warehouses, with priority.
 * Routing service picks the highest-priority warehouse whose pincode pattern matches.
 */
class CreateWarehouseZonesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'warehouse_id'    => ['type' => 'INT', 'unsigned' => true],
            'pincode_pattern' => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'e.g. 4000% (Mumbai), 11% (Delhi), 56% (Bangalore)'],
            'priority'        => ['type' => 'INT', 'default' => 100, 'comment' => 'Lower = preferred'],
            'estimated_days'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 4],
            'created_at'      => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('warehouse_id');
        $this->forge->addKey(['pincode_pattern', 'priority']);
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('warehouse_zones', true, ['ENGINE' => 'InnoDB']);
    }

    public function down() { $this->forge->dropTable('warehouse_zones', true); }
}
