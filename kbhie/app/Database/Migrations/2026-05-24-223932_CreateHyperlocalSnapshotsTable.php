<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateHyperlocalSnapshotsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'snapshot_date' => ['type' => 'DATE'],
            'window_days'   => ['type' => 'INT', 'unsigned' => true, 'default' => 30],
            'threshold'     => ['type' => 'INT', 'unsigned' => true, 'default' => 5],
            'gaps_json'     => ['type' => 'JSON', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('snapshot_date');
        $this->forge->createTable('hyperlocal_demand_snapshots', true, ['ENGINE' => 'InnoDB']);
    }

    public function down() { $this->forge->dropTable('hyperlocal_demand_snapshots', true); }
}
