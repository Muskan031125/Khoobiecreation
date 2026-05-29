<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `partial_venue` payment mode + `balance_due_payable_at` column so the
 * platform can support "pay 20% now, balance at the class/meetup/center".
 */
class AddPartialVenuePaymentMode extends Migration
{
    public function up()
    {
        // Extend the payment_mode enum
        $this->db->query("ALTER TABLE orders MODIFY payment_mode ENUM(
            'prepaid','cod','partial_cod','partial_venue','credit','gift_card','mixed','free_trial'
        ) NULL");

        $this->forge->addColumn('orders', [
            'balance_due_payable_at' => [
                'type'       => 'ENUM',
                'constraint' => ['delivery','venue','class','center','none'],
                'null'       => true,
                'default'    => null,
                'after'      => 'amount_due',
                'comment'    => 'Where the customer pays the remaining amount (cod = delivery, partial_venue = venue/class/center)',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('orders', 'balance_due_payable_at');
        $this->db->query("ALTER TABLE orders MODIFY payment_mode ENUM(
            'prepaid','cod','partial_cod','credit','gift_card','mixed'
        ) NULL");
    }
}
