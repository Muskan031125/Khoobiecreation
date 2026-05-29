<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBlogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 250],
            'slug'          => ['type' => 'VARCHAR', 'constraint' => 250, 'unique' => true],
            'excerpt'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'body_md'       => ['type' => 'MEDIUMTEXT'],
            'hero_image'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'tags'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'author_name'   => ['type' => 'VARCHAR', 'constraint' => 150, 'default' => 'Khoobie Editorial'],
            'ai_generated'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'seo_title'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'seo_description'=> ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['draft','published','archived'], 'default' => 'draft'],
            'views_count'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'published_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'    => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'published_at']);
        $this->forge->createTable('blogs', true, ['ENGINE' => 'InnoDB']);
    }

    public function down() { $this->forge->dropTable('blogs', true); }
}
