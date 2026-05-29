<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateEventsBookingsTables extends Migration
{
    public function up()
    {
        // events — workshops, classes, live sessions
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'type'            => ['type' => 'ENUM', 'constraint' => ['workshop','class','course','event','live_session'], 'default' => 'workshop'],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'hero_image'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'instructor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'instructor_bio'  => ['type' => 'TEXT', 'null' => true],
            'duration_minutes'=> ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'age_min_years'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'age_max_years'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'location_type'   => ['type' => 'ENUM', 'constraint' => ['physical','online','hybrid'], 'default' => 'online'],
            'physical_address'=> ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'online_url'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'comment' => 'Zoom/Meet link, sent on confirm'],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('events', true, ['ENGINE' => 'InnoDB']);

        // event_sessions — scheduled occurrences
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'starts_at'      => ['type' => 'DATETIME'],
            'ends_at'        => ['type' => 'DATETIME'],
            'capacity'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => '0 = unlimited'],
            'booked_count'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'price'          => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Overrides product price'],
            'status'         => ['type' => 'ENUM', 'constraint' => ['scheduled','open','sold_out','cancelled','completed'], 'default' => 'open'],
            'online_url'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['event_id', 'starts_at']);
        $this->forge->addKey(['status', 'starts_at']);
        $this->forge->addForeignKey('event_id', 'events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('event_sessions', true, ['ENGINE' => 'InnoDB']);

        // event_bookings
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'session_id'     => ['type' => 'BIGINT', 'unsigned' => true],
            'order_item_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'attendee_name'  => ['type' => 'VARCHAR', 'constraint' => 150],
            'attendee_age'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'parent_email'   => ['type' => 'VARCHAR', 'constraint' => 191],
            'parent_phone'   => ['type' => 'VARCHAR', 'constraint' => 20],
            'status'         => ['type' => 'ENUM', 'constraint' => ['confirmed','waitlist','attended','no_show','cancelled'], 'default' => 'confirmed'],
            'attended_at'    => ['type' => 'DATETIME', 'null' => true],
            'cancelled_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['session_id', 'status']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('session_id', 'event_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_item_id', 'order_items', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('event_bookings', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('event_bookings', true);
        $this->forge->dropTable('event_sessions', true);
        $this->forge->dropTable('events', true);
    }
}
