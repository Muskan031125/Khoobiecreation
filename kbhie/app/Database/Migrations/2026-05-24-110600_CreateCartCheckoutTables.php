<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateCartCheckoutTables extends Migration
{
    public function up()
    {
        // carts — guest (user_id null) + customer
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'anon_id'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'currency'         => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'INR'],
            'subtotal'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'discount_total'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'tax_total'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'shipping_total'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'grand_total'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'item_count'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'note'             => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'meta'             => ['type' => 'JSON', 'null' => true],
            'expires_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'       => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('anon_id');
        $this->forge->addKey('expires_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('carts', true, ['ENGINE' => 'InnoDB']);

        // cart_items
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cart_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id'     => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id'     => ['type' => 'BIGINT', 'unsigned' => true],
            'qty'            => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'unit_price'     => ['type' => 'INT', 'unsigned' => true, 'comment' => 'paise — snapshot'],
            'line_discount'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'line_tax'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'line_total'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_gift'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'gift_message'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'custom_fields'  => ['type' => 'JSON', 'null' => true, 'comment' => 'Personalization e.g. name on a book'],
            'created_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('cart_id');
        $this->forge->addKey('variant_id');
        $this->forge->addForeignKey('cart_id', 'carts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cart_items', true, ['ENGINE' => 'InnoDB']);

        // cart_applied_promotions
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cart_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'promotion_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'coupon_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'coupon_code'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'discount_amount' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'applied_data'    => ['type' => 'JSON', 'null' => true, 'comment' => 'Which lines, free items, etc'],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('cart_id');
        $this->forge->addKey('promotion_id');
        $this->forge->addForeignKey('cart_id', 'carts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('promotion_id', 'promotions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cart_applied_promotions', true, ['ENGINE' => 'InnoDB']);

        // checkout_sessions — track funnel + abandoned-cart recovery
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'cart_id'             => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'             => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'lead_id'             => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'step'                => ['type' => 'ENUM', 'constraint' => ['contact','shipping','payment','review','completed','abandoned'], 'default' => 'contact'],
            'contact_email'       => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'contact_phone'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'contact_name'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'shipping_address'    => ['type' => 'JSON', 'null' => true],
            'billing_address'     => ['type' => 'JSON', 'null' => true],
            'shipping_method'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'payment_method'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'cod_advance_paid'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise — for partial COD'],
            'recovery_sent_at'    => ['type' => 'DATETIME', 'null' => true],
            'recovery_count'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'started_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'abandoned_at'        => ['type' => 'DATETIME', 'null' => true],
            'completed_at'        => ['type' => 'DATETIME', 'null' => true],
            'order_id'            => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('cart_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey(['step', 'abandoned_at']);
        $this->forge->addForeignKey('cart_id', 'carts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('checkout_sessions', true, ['ENGINE' => 'InnoDB']);

        // wishlists
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'My Wishlist'],
            'is_public'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'share_token'=> ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('share_token');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('wishlists', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'wishlist_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'note'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['wishlist_id', 'product_id', 'variant_id']);
        $this->forge->addForeignKey('wishlist_id', 'wishlists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('wishlist_items', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('wishlist_items', true);
        $this->forge->dropTable('wishlists', true);
        $this->forge->dropTable('checkout_sessions', true);
        $this->forge->dropTable('cart_applied_promotions', true);
        $this->forge->dropTable('cart_items', true);
        $this->forge->dropTable('carts', true);
    }
}
