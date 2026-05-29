<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds hyperlocal locality + area to meetups so we can show
 * "Delhi > Rohini > Sector 7" instead of just "Delhi".
 *
 * Both are nullable for safe rollout; existing rows backfilled
 * by MarketplaceClassesSeeder::updateExistingMeetupLocations().
 */
class AddLocalityAreaToMeetups extends Migration
{
    public function up()
    {
        $this->forge->addColumn('meetups', [
            'locality' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'city',
                'comment'    => 'Sub-city area e.g. Rohini, Indiranagar, Sector 62',
            ],
            'area' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'locality',
                'comment'    => 'Finest grain e.g. Sector 7, Block A, Lane 5',
            ],
        ]);
        // Index for hyperlocal filtering
        $this->db->query("ALTER TABLE meetups ADD INDEX idx_city_locality (city, locality)");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE meetups DROP INDEX idx_city_locality");
        $this->forge->dropColumn('meetups', ['locality', 'area']);
    }
}
