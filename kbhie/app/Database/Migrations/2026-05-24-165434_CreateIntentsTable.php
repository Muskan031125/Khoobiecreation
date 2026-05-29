<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Unified intent capture. Backs every non-cart conversion across the platform:
 *   - tuition / course / membership free-trial signup
 *   - meetup free RSVP
 *   - meetup / premium-course PART-PAYMENT seat reservation
 *   - 1-on-1 service discovery call request
 *   - "Notify me when available" for OOS or upcoming launches
 *   - "Contact the instructor" with KHOOBIE attribution coupon
 *
 * Auto-attributed to anon_id (cookie) and user_id (if logged in) so we can
 * deduplicate, retarget, and measure conversion lift later.
 */
class CreateIntentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'product_type'    => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true,  'comment' => 'Denormalised for analytics'],
            'kind'            => ['type' => 'ENUM', 'constraint' => [
                'trial','rsvp','reserve_seat','discovery_call','notify_me','contact_instructor','enquire'
            ], 'comment' => 'What the visitor is asking for'],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
            'child_name'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'child_age'       => ['type' => 'TINYINT', 'unsigned' => true,  'null' => true],
            'preferred_slot'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'Free text or chosen schedule'],
            'message'         => ['type' => 'VARCHAR', 'constraint' => 1000,'null' => true],
            // OTP verification — same approach as auth flow
            'otp'             => ['type' => 'VARCHAR', 'constraint' => 6,   'null' => true],
            'otp_channel'     => ['type' => 'ENUM',    'constraint' => ['sms','whatsapp','email'], 'null' => true],
            'otp_sent_at'     => ['type' => 'DATETIME','null' => true],
            'verified_at'     => ['type' => 'DATETIME','null' => true],
            // Part-payment for seat reservations (e.g. ₹100 to block a ₹2000 weekend meetup)
            'amount_due'      => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Total fee (paise)'],
            'amount_paid'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0,  'comment' => 'Part-payment captured (paise)'],
            'payment_gateway' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'gateway_ref'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => [
                'pending','verified','reserved','converted','contacted','cancelled','no_show'
            ], 'default' => 'pending'],
            'anon_id'         => ['type' => 'VARCHAR', 'constraint' => 64,  'null' => true],
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true,    'null' => true],
            'attribution_code'=> ['type' => 'VARCHAR', 'constraint' => 32,  'null' => true, 'default' => 'KHOOBIE',
                                  'comment' => 'Used when the tutor takes the lead off-platform'],
            'metadata'        => ['type' => 'JSON', 'null' => true],
            'ip'              => ['type' => 'VARCHAR', 'constraint' => 64,  'null' => true],
            'user_agent'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['product_id', 'kind', 'status']);
        $this->forge->addKey(['phone']);
        $this->forge->addKey(['email']);
        $this->forge->addKey(['anon_id']);
        $this->forge->addKey(['user_id']);
        $this->forge->addKey(['created_at']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('intents', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('intents', true);
    }
}
