<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateAffiliatesTables extends Migration
{
    public function up()
    {
        // affiliates
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'            => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'code'               => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'              => ['type' => 'VARCHAR', 'constraint' => 191],
            'phone'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'instagram_handle'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'commission_pct'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 10],
            'commission_type'    => ['type' => 'ENUM', 'constraint' => ['percent','flat'], 'default' => 'percent'],
            'cookie_days'        => ['type' => 'INT', 'unsigned' => true, 'default' => 30],
            'status'             => ['type' => 'ENUM', 'constraint' => ['pending','active','paused','suspended'], 'default' => 'pending'],
            'payout_method'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => 'bank|upi|paypal'],
            'payout_details'     => ['type' => 'JSON', 'null' => true],
            'balance'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise unpaid'],
            'lifetime_earnings'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'lifetime_clicks'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'lifetime_conversions' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'         => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('affiliates', true, ['ENGINE' => 'InnoDB']);

        // Add FK from orders.affiliate_id now that affiliates exists
        $this->db->query('ALTER TABLE orders ADD CONSTRAINT orders_affiliate_fk FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // affiliate_clicks
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'affiliate_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'anon_id'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'landing_url'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'referer'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ip'           => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['affiliate_id', 'created_at']);
        $this->forge->addForeignKey('affiliate_id', 'affiliates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('affiliate_clicks', true, ['ENGINE' => 'InnoDB']);

        // affiliate_conversions
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'affiliate_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'order_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'gross_amount'    => ['type' => 'INT', 'unsigned' => true],
            'commission'      => ['type' => 'INT', 'unsigned' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['pending','approved','paid','reversed','cancelled'], 'default' => 'pending'],
            'approved_at'     => ['type' => 'DATETIME', 'null' => true],
            'paid_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['affiliate_id', 'order_id']);
        $this->forge->addKey(['status', 'approved_at']);
        $this->forge->addForeignKey('affiliate_id', 'affiliates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('affiliate_conversions', true, ['ENGINE' => 'InnoDB']);

        // affiliate_payouts
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'affiliate_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'amount'       => ['type' => 'INT', 'unsigned' => true],
            'period_start' => ['type' => 'DATE'],
            'period_end'   => ['type' => 'DATE'],
            'status'       => ['type' => 'ENUM', 'constraint' => ['pending','processing','paid','failed'], 'default' => 'pending'],
            'method'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'reference'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'UTR / txn id'],
            'paid_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['affiliate_id', 'status']);
        $this->forge->addForeignKey('affiliate_id', 'affiliates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('affiliate_payouts', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('affiliate_payouts', true);
        $this->forge->dropTable('affiliate_conversions', true);
        $this->forge->dropTable('affiliate_clicks', true);
        $this->db->query('ALTER TABLE orders DROP FOREIGN KEY orders_affiliate_fk');
        $this->forge->dropTable('affiliates', true);
    }
}
