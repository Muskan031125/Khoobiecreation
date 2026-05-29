<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Digital download delivery — one record per (order_item, file) pair.
 * Each gets a tokenised URL that lets the buyer download N times before expiry.
 *
 * Pre-existing product_files table holds the file URLs; this table tracks
 * the per-buyer DELIVERY of those files.
 */
class CreateDigitalDownloadsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'order_item_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'file_url'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'file_name'       => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'file_size_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'token'           => ['type' => 'VARCHAR', 'constraint' => 64, 'unique' => true],
            'downloads_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'max_downloads'   => ['type' => 'INT', 'unsigned' => true, 'default' => 10],
            'expires_at'      => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Null = forever'],
            'first_downloaded_at' => ['type' => 'DATETIME', 'null' => true],
            'last_downloaded_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('order_id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('digital_downloads', true, ['ENGINE' => 'InnoDB']);
    }

    public function down() { $this->forge->dropTable('digital_downloads', true); }
}
