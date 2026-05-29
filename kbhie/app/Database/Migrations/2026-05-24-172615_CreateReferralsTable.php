<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Referral program — every user gets a unique code (e.g. RIYA-A8F2).
 * Sharing a link like /r/RIYA-A8F2 sets a cookie + tracks the referee on signup
 * and first purchase. Referrer gets 200 Khoobie points on referee's first order,
 * referee gets a 10% off coupon on first order.
 */
class CreateReferralsTable extends Migration
{
    public function up()
    {
        // Add referral_code to users (one per user, generated on signup)
        $this->forge->addColumn('users', [
            'referral_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'phone',
                'unique'     => true,
            ],
            'referred_by_user_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'referral_code',
            ],
        ]);

        // Track every referral attribution event
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'referrer_user_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'referee_user_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'comment' => 'Null until referee signs up'],
            'referee_anon_id'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'code_used'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'first_order_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'first_order_amount'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'referrer_points'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'Points awarded to referrer'],
            'referee_coupon_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => [
                'cookied','signed_up','converted','rewarded','flagged','cancelled'
            ], 'default' => 'cookied'],
            'channel'           => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => 'whatsapp/email/instagram/direct'],
            'utm_source'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'utm_medium'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'utm_campaign'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'cookied_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'signed_up_at'      => ['type' => 'DATETIME', 'null' => true],
            'converted_at'      => ['type' => 'DATETIME', 'null' => true],
            'rewarded_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('referrer_user_id');
        $this->forge->addKey('referee_user_id');
        $this->forge->addKey(['status', 'cookied_at']);
        $this->forge->addForeignKey('referrer_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('referee_user_id',  'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('referrals', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('referrals', true);
        $this->forge->dropColumn('users', ['referral_code', 'referred_by_user_id']);
    }
}
