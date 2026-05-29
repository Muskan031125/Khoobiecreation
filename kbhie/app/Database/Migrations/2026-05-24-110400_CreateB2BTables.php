<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateB2BTables extends Migration
{
    public function up()
    {
        // customer_groups — retail/wholesale/educator/corporate/school
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'                => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'default_discount_pct'=> ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'requires_approval'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'tax_exempt'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('customer_groups', true, ['ENGINE' => 'InnoDB']);

        // customer_group_users
        $this->forge->addField([
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'group_id'        => ['type' => 'INT', 'unsigned' => true],
            'approved_at'     => ['type' => 'DATETIME', 'null' => true],
            'approved_by'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey(['user_id', 'group_id']);
        $this->forge->addKey('group_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('group_id', 'customer_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_group_users', true, ['ENGINE' => 'InnoDB']);

        // price_tiers — volume / group-based pricing per variant
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'variant_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'customer_group_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL = all customers'],
            'min_qty'           => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'price'             => ['type' => 'INT', 'unsigned' => true, 'comment' => 'paise'],
            'starts_at'         => ['type' => 'DATETIME', 'null' => true],
            'ends_at'           => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['variant_id', 'customer_group_id', 'min_qty']);
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('customer_group_id', 'customer_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('price_tiers', true, ['ENGINE' => 'InnoDB']);

        // customer_credit — B2B credit terms / wallet
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'credit_limit'    => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'credit_used'     => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'payment_terms_days' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_enabled'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_credit', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('customer_credit', true);
        $this->forge->dropTable('price_tiers', true);
        $this->forge->dropTable('customer_group_users', true);
        $this->forge->dropTable('customer_groups', true);
    }
}
