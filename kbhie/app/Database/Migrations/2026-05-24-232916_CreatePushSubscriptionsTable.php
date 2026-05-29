<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePushSubscriptionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'anon_id'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'endpoint'   => ['type' => 'VARCHAR', 'constraint' => 500],
            'p256dh_key' => ['type' => 'VARCHAR', 'constraint' => 200],
            'auth_token' => ['type' => 'VARCHAR', 'constraint' => 100],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('endpoint');
        $this->forge->addKey('user_id');
        $this->forge->createTable('push_subscriptions', true, ['ENGINE' => 'InnoDB']);
    }

    public function down() { $this->forge->dropTable('push_subscriptions', true); }
}
