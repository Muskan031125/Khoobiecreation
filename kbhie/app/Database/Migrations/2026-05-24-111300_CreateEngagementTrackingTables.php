<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateEngagementTrackingTables extends Migration
{
    public function up()
    {
        // reviews
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'order_item_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_id'           => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'reviewer_name'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'reviewer_email'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'rating'            => ['type' => 'TINYINT', 'unsigned' => true, 'comment' => '1-5'],
            'title'             => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'body'              => ['type' => 'TEXT', 'null' => true],
            'is_verified_buyer' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'            => ['type' => 'ENUM', 'constraint' => ['pending','published','rejected','spam'], 'default' => 'pending'],
            'helpful_count'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reply_body'        => ['type' => 'TEXT', 'null' => true, 'comment' => 'Brand response'],
            'replied_at'        => ['type' => 'DATETIME', 'null' => true],
            'replied_by'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['product_id', 'status', 'rating']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_item_id', 'order_items', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('reviews', true, ['ENGINE' => 'InnoDB']);

        // review_media
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'review_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'type'       => ['type' => 'ENUM', 'constraint' => ['image','video']],
            'path'       => ['type' => 'VARCHAR', 'constraint' => 500],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('review_id');
        $this->forge->addForeignKey('review_id', 'reviews', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('review_media', true, ['ENGINE' => 'InnoDB']);

        // product_questions
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'asker_name'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'question'     => ['type' => 'TEXT'],
            'answer'       => ['type' => 'TEXT', 'null' => true],
            'answered_by'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'answered_at'  => ['type' => 'DATETIME', 'null' => true],
            'is_published' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'helpful_count'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['product_id', 'is_published']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_questions', true, ['ENGINE' => 'InnoDB']);

        // tracking_events — server-side mirror of every pixel event
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'        => ['type' => 'VARCHAR', 'constraint' => 64, 'comment' => 'Dedup ID shared client+server'],
            'event_name'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'anon_id'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'lead_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'order_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'value'           => ['type' => 'INT', 'null' => true, 'comment' => 'paise — for purchase events'],
            'currency'        => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'INR'],
            'url'             => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'referrer'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ip'              => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'fbp'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fbc'             => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'ga_client_id'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ga_session_id'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'payload'         => ['type' => 'JSON', 'null' => true],
            'source'          => ['type' => 'ENUM', 'constraint' => ['client','server','webhook','import'], 'default' => 'client'],
            'sent_to_meta_at' => ['type' => 'DATETIME', 'null' => true],
            'sent_to_ga_at'   => ['type' => 'DATETIME', 'null' => true],
            'meta_response'   => ['type' => 'JSON', 'null' => true],
            'ga_response'     => ['type' => 'JSON', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('event_id');
        $this->forge->addKey(['event_name', 'created_at']);
        $this->forge->addKey(['anon_id']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tracking_events', true, ['ENGINE' => 'InnoDB']);

        // notifications_log — every outbound message
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'channel'      => ['type' => 'ENUM', 'constraint' => ['email','sms','whatsapp','push','internal']],
            'recipient'    => ['type' => 'VARCHAR', 'constraint' => 191],
            'user_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'template_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'subject'      => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'payload'      => ['type' => 'JSON', 'null' => true],
            'provider'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'provider_id'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['queued','sent','delivered','read','failed','bounced'], 'default' => 'queued'],
            'error'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ref_type'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ref_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'queued_at'    => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'sent_at'      => ['type' => 'DATETIME', 'null' => true],
            'delivered_at' => ['type' => 'DATETIME', 'null' => true],
            'read_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['channel', 'status']);
        $this->forge->addKey(['user_id', 'queued_at']);
        $this->forge->addKey(['ref_type', 'ref_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('notifications_log', true, ['ENGINE' => 'InnoDB']);

        // webhook_log — inbound webhook audit
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'source'          => ['type' => 'VARCHAR', 'constraint' => 50],
            'event'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'reference_id'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'headers'         => ['type' => 'JSON', 'null' => true],
            'payload'         => ['type' => 'JSON', 'null' => true],
            'signature_valid' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'processed_at'    => ['type' => 'DATETIME', 'null' => true],
            'success'         => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'error'           => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['source', 'event', 'created_at']);
        $this->forge->addKey('reference_id');
        $this->forge->createTable('webhook_log', true, ['ENGINE' => 'InnoDB']);

        // subscribers — newsletter + WhatsApp broadcast list
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'source'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tags'            => ['type' => 'JSON', 'null' => true],
            'consent_email'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'consent_sms'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'consent_whatsapp'=> ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'unsubscribed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('phone');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('subscribers', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('subscribers', true);
        $this->forge->dropTable('webhook_log', true);
        $this->forge->dropTable('notifications_log', true);
        $this->forge->dropTable('tracking_events', true);
        $this->forge->dropTable('product_questions', true);
        $this->forge->dropTable('review_media', true);
        $this->forge->dropTable('reviews', true);
    }
}
