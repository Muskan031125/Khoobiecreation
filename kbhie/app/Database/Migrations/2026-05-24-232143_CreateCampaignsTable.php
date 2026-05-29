<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateCampaignsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'subject'      => ['type' => 'VARCHAR', 'constraint' => 250],
            'channel'      => ['type' => 'ENUM', 'constraint' => ['email','whatsapp','sms'], 'default' => 'email'],
            'body_html'    => ['type' => 'MEDIUMTEXT', 'null' => true],
            'audience'     => ['type' => 'ENUM', 'constraint' => ['all','active_customers','recent_buyers','by_city','by_tier','unverified','abandoned_cart'], 'default' => 'all'],
            'audience_arg' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ai_generated' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'       => ['type' => 'ENUM', 'constraint' => ['draft','scheduled','sending','sent','cancelled'], 'default' => 'draft'],
            'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
            'sent_at'      => ['type' => 'DATETIME', 'null' => true],
            'recipients_n' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'opens_n'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'clicks_n'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_by'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'   => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status','scheduled_at']);
        $this->forge->createTable('campaigns', true, ['ENGINE' => 'InnoDB']);
    }

    public function down() { $this->forge->dropTable('campaigns', true); }
}
