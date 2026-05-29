<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSecurityTables extends Migration
{
    public function up()
    {
        // Track every authentication attempt — used for lockout + audit
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'identifier'    => ['type' => 'VARCHAR', 'constraint' => 191, 'comment' => 'email/phone tried'],
            'user_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'kind'          => ['type' => 'ENUM', 'constraint' => ['login_pwd','login_otp','signup','password_reset','admin_login','partner_login'], 'default' => 'login_pwd'],
            'ip'            => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'success'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'reason'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['identifier', 'created_at']);
        $this->forge->addKey(['ip', 'created_at']);
        $this->forge->addKey(['kind', 'success', 'created_at']);
        $this->forge->createTable('login_attempts', true, ['ENGINE' => 'InnoDB']);

        // Account lockouts (active locks)
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'identifier'    => ['type' => 'VARCHAR', 'constraint' => 191, 'comment' => 'email, phone, or ip:1.2.3.4'],
            'type'          => ['type' => 'ENUM', 'constraint' => ['account','ip'], 'default' => 'account'],
            'reason'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'attempts'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'locked_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'expires_at'    => ['type' => 'DATETIME'],
            'unlocked_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['identifier', 'type', 'expires_at']);
        $this->forge->addKey('expires_at');
        $this->forge->createTable('lockouts', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('lockouts', true);
        $this->forge->dropTable('login_attempts', true);
    }
}
