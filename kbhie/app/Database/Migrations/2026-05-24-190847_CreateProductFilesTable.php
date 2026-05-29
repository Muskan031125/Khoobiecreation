<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateProductFilesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'file_url'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'file_name'       => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'file_size_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'mime_type'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_sample'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => 'Free preview file'],
            'sort_order'      => ['type' => 'INT', 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['product_id', 'sort_order']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_files', true, ['ENGINE' => 'InnoDB']);
    }

    public function down() { $this->forge->dropTable('product_files', true); }
}
