<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdentityTables extends Migration
{
    public function up()
    {
        // users
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'email'               => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'phone'               => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'password_hash'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'avatar'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['active','disabled','pending'], 'default' => 'active'],
            'email_verified_at'   => ['type' => 'DATETIME', 'null' => true],
            'phone_verified_at'   => ['type' => 'DATETIME', 'null' => true],
            'last_login_at'       => ['type' => 'DATETIME', 'null' => true],
            'meta'                => ['type' => 'JSON', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'updated_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('phone');
        $this->forge->addKey('status');
        $this->forge->createTable('users', true, ['ENGINE' => 'InnoDB']);

        // roles
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'label'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'permissions' => ['type' => 'JSON', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('roles', true, ['ENGINE' => 'InnoDB']);

        // user_roles
        $this->forge->addField([
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'role_id'    => ['type' => 'INT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey(['user_id', 'role_id']);
        $this->forge->addKey('role_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_roles', true, ['ENGINE' => 'InnoDB']);

        // addresses
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'type'       => ['type' => 'ENUM', 'constraint' => ['shipping','billing','both'], 'default' => 'both'],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'line1'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'line2'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'landmark'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'city'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'state'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'pincode'    => ['type' => 'VARCHAR', 'constraint' => 10],
            'country'    => ['type' => 'CHAR', 'constraint' => 2, 'default' => 'IN'],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'is_default']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('addresses', true, ['ENGINE' => 'InnoDB']);

        // leads — pre-signup capture
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'anon_id'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'user_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'phone'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'city'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pincode'     => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'source'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'landing_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'utm_source'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'utm_medium'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'utm_campaign'=> ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'utm_term'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'utm_content' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fbclid'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'gclid'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip'          => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'captured_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'updated_at'  => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('anon_id');
        $this->forge->addKey('email');
        $this->forge->addKey('phone');
        $this->forge->addKey('user_id');
        $this->forge->addKey('source');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('leads', true, ['ENGINE' => 'InnoDB']);

        // lead_form_submissions — audit every form submit
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'lead_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'form_key'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'payload'    => ['type' => 'JSON', 'null' => true],
            'ip'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['form_key', 'created_at']);
        $this->forge->addKey('lead_id');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('lead_form_submissions', true, ['ENGINE' => 'InnoDB']);

        // auth_otps
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'identifier' => ['type' => 'VARCHAR', 'constraint' => 191, 'comment' => 'email or phone'],
            'channel'    => ['type' => 'ENUM', 'constraint' => ['email','sms','whatsapp']],
            'code_hash'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'purpose'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'attempts'   => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['identifier', 'purpose']);
        $this->forge->addKey('expires_at');
        $this->forge->createTable('auth_otps', true, ['ENGINE' => 'InnoDB']);

        // auth_sessions (long-lived remember-me tokens)
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'token_hash'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'ip'           => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'last_seen_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at'   => ['type' => 'DATETIME'],
            'revoked_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('token_hash');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('auth_sessions', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('auth_sessions', true);
        $this->forge->dropTable('auth_otps', true);
        $this->forge->dropTable('lead_form_submissions', true);
        $this->forge->dropTable('leads', true);
        $this->forge->dropTable('addresses', true);
        $this->forge->dropTable('user_roles', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('users', true);
    }
}
