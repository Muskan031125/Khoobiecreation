<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateGiftCardsTables extends Migration
{
    public function up()
    {
        // gift_cards
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 30],
            'pin_hash'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'Required for redemption'],
            'initial_value'     => ['type' => 'INT', 'unsigned' => true, 'comment' => 'paise'],
            'balance'           => ['type' => 'INT', 'unsigned' => true, 'comment' => 'paise'],
            'currency'          => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'INR'],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active','redeemed','expired','disabled'], 'default' => 'active'],
            'purchaser_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'purchase_order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'recipient_name'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'recipient_email'   => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'recipient_phone'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'message'           => ['type' => 'TEXT', 'null' => true],
            'design_template'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'delivery_at'       => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Scheduled send'],
            'delivered_at'      => ['type' => 'DATETIME', 'null' => true],
            'expires_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['status', 'expires_at']);
        $this->forge->addForeignKey('purchaser_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('purchase_order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('gift_cards', true, ['ENGINE' => 'InnoDB']);

        // gift_card_transactions — ledger
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'gift_card_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'type'           => ['type' => 'ENUM', 'constraint' => ['issue','redeem','topup','adjustment','refund','expiry']],
            'amount'         => ['type' => 'INT'],
            'balance_after'  => ['type' => 'INT', 'unsigned' => true],
            'order_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'comment' => 'Who redeemed'],
            'note'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['gift_card_id', 'created_at']);
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('gift_card_id', 'gift_cards', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('gift_card_transactions', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('gift_card_transactions', true);
        $this->forge->dropTable('gift_cards', true);
    }
}
