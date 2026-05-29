<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSettingsTables extends Migration
{
    public function up()
    {
        // settings — generic key/value config (company info, COD limits, free shipping threshold, etc)
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'group_key'  => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'value_type' => ['type' => 'ENUM', 'constraint' => ['string','int','bool','json','encrypted'], 'default' => 'string'],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'description'=> ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_public'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => 'Safe to expose to frontend'],
            'updated_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['group_key', 'key']);
        $this->forge->createTable('settings', true, ['ENGINE' => 'InnoDB']);

        // popups — lead-capture modal configuration
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'trigger'         => ['type' => 'ENUM', 'constraint' => ['time_delay','scroll_percent','exit_intent','page_view_count','manual'], 'default' => 'time_delay'],
            'trigger_value'   => ['type' => 'INT', 'default' => 5],
            'url_pattern'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'Wildcard match'],
            'audience'        => ['type' => 'JSON', 'null' => true, 'comment' => 'Targeting rules'],
            'frequency_days'  => ['type' => 'INT', 'unsigned' => true, 'default' => 7, 'comment' => 'Show once per N days'],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 200],
            'subtitle'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'image'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cta_text'        => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'Get my discount'],
            'reward_type'     => ['type' => 'ENUM', 'constraint' => ['coupon','raffle','gift','newsletter','none'], 'default' => 'coupon'],
            'reward_coupon_id'=> ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'reward_message'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'capture_fields'  => ['type' => 'JSON', 'null' => true, 'comment' => '[email, phone, name, etc]'],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'starts_at'       => ['type' => 'DATETIME', 'null' => true],
            'ends_at'         => ['type' => 'DATETIME', 'null' => true],
            'shown_count'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'converted_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['is_active', 'trigger']);
        $this->forge->addForeignKey('reward_coupon_id', 'coupons', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('popups', true, ['ENGINE' => 'InnoDB']);

        // banners — site-wide promotional banners (top bar)
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'text'         => ['type' => 'VARCHAR', 'constraint' => 500],
            'link_url'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'bg_color'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '#FF6F61'],
            'text_color'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '#FFFFFF'],
            'placement'    => ['type' => 'ENUM', 'constraint' => ['top_bar','homepage_hero','category_top','pdp_above_atc'], 'default' => 'top_bar'],
            'priority'     => ['type' => 'INT', 'default' => 100],
            'starts_at'    => ['type' => 'DATETIME', 'null' => true],
            'ends_at'      => ['type' => 'DATETIME', 'null' => true],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['placement', 'is_active', 'priority']);
        $this->forge->createTable('banners', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('banners', true);
        $this->forge->dropTable('popups', true);
        $this->forge->dropTable('settings', true);
    }
}
