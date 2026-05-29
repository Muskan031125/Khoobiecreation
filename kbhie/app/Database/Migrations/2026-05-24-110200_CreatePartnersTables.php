<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePartnersTables extends Migration
{
    public function up()
    {
        // partners — vendor master
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_name'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'gstin'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'pan'               => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'contact_name'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'phone'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'address_line1'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'address_line2'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'city'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'state'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pincode'           => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'fulfillment_type'  => ['type' => 'ENUM', 'constraint' => ['drop_ship','warehouse_deliver','both'], 'default' => 'drop_ship'],
            'commission_pct'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'bank_name'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'bank_account'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'bank_ifsc'         => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active','pending','suspended'], 'default' => 'pending'],
            'notes'             => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->createTable('partners', true, ['ENGINE' => 'InnoDB']);

        // Now that partners exists, add FK on products.partner_id
        $this->db->query('ALTER TABLE products ADD CONSTRAINT products_partner_fk FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // partner_users — login accounts for partners
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'partner_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'role'       => ['type' => 'ENUM', 'constraint' => ['owner','manager','staff'], 'default' => 'staff'],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['partner_id', 'user_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('partner_users', true, ['ENGINE' => 'InnoDB']);

        // partner_payouts
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'partner_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'period_start' => ['type' => 'DATE'],
            'period_end'   => ['type' => 'DATE'],
            'gross_amount' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'commission'   => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'adjustments'  => ['type' => 'BIGINT', 'default' => 0, 'comment' => 'paise, can be negative'],
            'net_payable'  => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'status'       => ['type' => 'ENUM', 'constraint' => ['pending','approved','paid','cancelled'], 'default' => 'pending'],
            'utr'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'paid_at'      => ['type' => 'DATETIME', 'null' => true],
            'notes'        => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['partner_id', 'status']);
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('partner_payouts', true, ['ENGINE' => 'InnoDB']);

        // partner_payout_items — line-level traceability (FK to order_items added later)
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'payout_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'order_item_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'amount'         => ['type' => 'BIGINT', 'unsigned' => true, 'comment' => 'paise'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('payout_id');
        $this->forge->addKey('order_item_id');
        $this->forge->addForeignKey('payout_id', 'partner_payouts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('partner_payout_items', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('partner_payout_items', true);
        $this->forge->dropTable('partner_payouts', true);
        $this->forge->dropTable('partner_users', true);
        $this->db->query('ALTER TABLE products DROP FOREIGN KEY products_partner_fk');
        $this->forge->dropTable('partners', true);
    }
}
