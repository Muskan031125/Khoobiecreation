<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds attribution JSON to orders + intents so we can answer
 * "where did this revenue come from?" by source / medium / campaign / ref code.
 */
class AddAttributionToOrdersAndIntents extends Migration
{
    public function up()
    {
        $this->forge->addColumn('orders', [
            'attribution' => [
                'type'    => 'JSON',
                'null'    => true,
                'comment' => 'First-touch + last-touch utm/gclid/fbclid/ref snapshot',
            ],
        ]);
        $this->forge->addColumn('intents', [
            'attribution' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('orders', 'attribution');
        $this->forge->dropColumn('intents', 'attribution');
    }
}
