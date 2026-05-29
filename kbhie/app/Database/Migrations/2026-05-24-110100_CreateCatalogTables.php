<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateCatalogTables extends Migration
{
    public function up()
    {
        // categories — tree (parent_id self-ref)
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'parent_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'hero_image'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'icon'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sort_order'      => ['type' => 'INT', 'default' => 0],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'seo_title'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'seo_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['parent_id', 'sort_order']);
        $this->forge->addKey('is_active');
        $this->forge->createTable('categories', true, ['ENGINE' => 'InnoDB']);
        $this->db->query('ALTER TABLE categories ADD CONSTRAINT categories_parent_fk FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // products
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sku'             => ['type' => 'VARCHAR', 'constraint' => 64],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 250],
            'type'            => ['type' => 'ENUM', 'constraint' => ['simple','variable','bundle','digital','event','subscription'], 'default' => 'simple'],
            'short_desc'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'long_desc'       => ['type' => 'MEDIUMTEXT', 'null' => true],
            'hero_image'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'gallery'         => ['type' => 'JSON', 'null' => true],
            'video_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['draft','active','out_of_stock','discontinued'], 'default' => 'draft'],
            'is_featured'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'partner_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'comment' => 'Vendor who owns/fulfills this SKU'],
            'tax_class_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'hsn_code'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'age_min_years'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'age_max_years'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'rating_avg'      => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0],
            'rating_count'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'sales_count'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'seo_title'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'seo_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'schema_org'      => ['type' => 'JSON', 'null' => true, 'comment' => 'Custom Schema.org overrides'],
            'rich_blocks'     => ['type' => 'JSON', 'null' => true, 'comment' => 'A+ PDP content blocks'],
            'meta'            => ['type' => 'JSON', 'null' => true],
            'published_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('sku');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['status', 'is_featured']);
        $this->forge->addKey('partner_id');
        $this->forge->addKey('type');
        $this->forge->createTable('products', true, ['ENGINE' => 'InnoDB']);
        // FULLTEXT index on name + short_desc + long_desc
        $this->db->query('ALTER TABLE products ADD FULLTEXT KEY products_ft (name, short_desc, long_desc)');

        // product_variants
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'sku'              => ['type' => 'VARCHAR', 'constraint' => 64],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'e.g. "Age 5-7 / English"'],
            'price'            => ['type' => 'INT', 'unsigned' => true, 'comment' => 'paise'],
            'compare_at_price' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'paise — strike-through'],
            'cost'             => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'paise'],
            'weight_g'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'length_mm'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'width_mm'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'height_mm'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'barcode'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'image'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_default'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'       => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('sku');
        $this->forge->addKey(['product_id', 'is_active']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_variants', true, ['ENGINE' => 'InnoDB']);

        // product_categories
        $this->forge->addField([
            'product_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey(['product_id', 'category_id']);
        $this->forge->addKey('category_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_categories', true, ['ENGINE' => 'InnoDB']);

        // product_attributes — per-product key/value (specs)
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'group_key'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'specs|features|materials'],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'value'      => ['type' => 'VARCHAR', 'constraint' => 500],
            'is_filterable' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['product_id', 'key']);
        $this->forge->addKey(['key', 'value', 'is_filterable']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_attributes', true, ['ENGINE' => 'InnoDB']);

        // product_attribute_options — global filter definitions
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'attribute_key'  => ['type' => 'VARCHAR', 'constraint' => 80],
            'value'          => ['type' => 'VARCHAR', 'constraint' => 200],
            'label'          => ['type' => 'VARCHAR', 'constraint' => 200],
            'group_label'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['attribute_key', 'value']);
        $this->forge->createTable('product_attribute_options', true, ['ENGINE' => 'InnoDB']);

        // variant_attributes — what makes this variant distinct
        $this->forge->addField([
            'variant_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'attribute_key' => ['type' => 'VARCHAR', 'constraint' => 80],
            'value'         => ['type' => 'VARCHAR', 'constraint' => 200],
        ]);
        $this->forge->addPrimaryKey(['variant_id', 'attribute_key']);
        $this->forge->addKey(['attribute_key', 'value']);
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('variant_attributes', true, ['ENGINE' => 'InnoDB']);

        // bundle_items — components of a bundle product
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'bundle_product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'child_product_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'child_variant_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'quantity'          => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'discount_pct'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'sort_order'        => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('bundle_product_id');
        $this->forge->addForeignKey('bundle_product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('child_product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('child_variant_id', 'product_variants', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('bundle_items', true, ['ENGINE' => 'InnoDB']);

        // digital_assets — downloadable files for digital products
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'file_path'       => ['type' => 'VARCHAR', 'constraint' => 500, 'comment' => 'Storage path or S3 key'],
            'file_size'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'mime_type'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'version'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'license_type'    => ['type' => 'ENUM', 'constraint' => ['personal','classroom','commercial'], 'default' => 'personal'],
            'download_limit'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL = unlimited'],
            'expiry_days'     => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Days after purchase'],
            'sort_order'      => ['type' => 'INT', 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('digital_assets', true, ['ENGINE' => 'InnoDB']);

        // product_related — manual upsells/cross-sells/FBT
        $this->forge->addField([
            'product_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'related_product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'type'               => ['type' => 'ENUM', 'constraint' => ['upsell','cross_sell','frequently_bought','similar']],
            'sort_order'         => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey(['product_id', 'related_product_id', 'type']);
        $this->forge->addKey('related_product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('related_product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_related', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('product_related', true);
        $this->forge->dropTable('digital_assets', true);
        $this->forge->dropTable('bundle_items', true);
        $this->forge->dropTable('variant_attributes', true);
        $this->forge->dropTable('product_attribute_options', true);
        $this->forge->dropTable('product_attributes', true);
        $this->forge->dropTable('product_categories', true);
        $this->forge->dropTable('product_variants', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('categories', true);
    }
}
