<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateApiTokensTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'token'      => ['type' => 'CHAR', 'constraint' => 64, 'comment' => 'sha256 of the bearer token'],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'App name (mobile-app, partner-app)'],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('api_tokens', true, ['ENGINE' => 'InnoDB']);
    }

    public function down() { $this->forge->dropTable('api_tokens', true); }
}
