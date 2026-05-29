<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateInventoryTables extends Migration
{
    public function up()
    {
        // warehouses
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'code'         => ['type' => 'VARCHAR', 'constraint' => 30],
            'type'         => ['type' => 'ENUM', 'constraint' => ['own','partner','virtual'], 'default' => 'own'],
            'partner_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'address_line' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'city'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'state'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pincode'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'is_default'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('partner_id');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('warehouses', true, ['ENGINE' => 'InnoDB']);

        // inventory — qty per variant per warehouse
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'variant_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'warehouse_id'    => ['type' => 'INT', 'unsigned' => true],
            'qty_on_hand'     => ['type' => 'INT', 'default' => 0],
            'qty_reserved'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'qty_incoming'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reorder_level'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reorder_qty'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['variant_id', 'warehouse_id']);
        $this->forge->addKey('warehouse_id');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('inventory', true, ['ENGINE' => 'InnoDB']);

        // inventory_movements — full audit
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'variant_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'warehouse_id' => ['type' => 'INT', 'unsigned' => true],
            'change_qty'   => ['type' => 'INT', 'comment' => 'positive = in, negative = out'],
            'balance_after'=> ['type' => 'INT', 'null' => true],
            'reason'       => ['type' => 'ENUM', 'constraint' => ['sale','return','restock','adjustment','transfer_in','transfer_out','reserve','release','damaged']],
            'ref_type'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ref_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'note'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['variant_id', 'warehouse_id', 'created_at']);
        $this->forge->addKey(['ref_type', 'ref_id']);
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('inventory_movements', true, ['ENGINE' => 'InnoDB']);

        // stock_alerts — notify-when-back-in-stock (customer-facing)
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'channel'    => ['type' => 'ENUM', 'constraint' => ['email','whatsapp','sms'], 'default' => 'email'],
            'notified_at'=> ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['variant_id', 'notified_at']);
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('stock_alerts', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('stock_alerts', true);
        $this->forge->dropTable('inventory_movements', true);
        $this->forge->dropTable('inventory', true);
        $this->forge->dropTable('warehouses', true);
    }
}
