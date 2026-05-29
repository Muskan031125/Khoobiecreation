<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateGstInvoicingTables extends Migration
{
    public function up()
    {
        // tax_classes — e.g. "GST 5%", "GST 12%", "GST 18%", "Exempt"
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'rate_pct'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'is_inclusive' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => 'Prices include tax'],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('tax_classes', true, ['ENGINE' => 'InnoDB']);

        // Add FK from products.tax_class_id now that tax_classes exists
        $this->db->query('ALTER TABLE products ADD CONSTRAINT products_tax_fk FOREIGN KEY (tax_class_id) REFERENCES tax_classes(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // tax_rates — per-state CGST/SGST/IGST split
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tax_class_id'  => ['type' => 'INT', 'unsigned' => true],
            'state_code'    => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'comment' => 'IN-MH etc; NULL = default'],
            'cgst_rate'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'sgst_rate'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'igst_rate'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'cess_rate'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tax_class_id', 'state_code']);
        $this->forge->addForeignKey('tax_class_id', 'tax_classes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tax_rates', true, ['ENGINE' => 'InnoDB']);

        // invoices — GST-compliant invoice register
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'invoice_number'    => ['type' => 'VARCHAR', 'constraint' => 30],
            'invoice_type'      => ['type' => 'ENUM', 'constraint' => ['tax_invoice','bill_of_supply','credit_note','debit_note','proforma'], 'default' => 'tax_invoice'],
            'fy'                => ['type' => 'VARCHAR', 'constraint' => 9, 'comment' => 'e.g. 2026-2027'],
            'invoice_date'      => ['type' => 'DATE'],
            'place_of_supply'   => ['type' => 'VARCHAR', 'constraint' => 5, 'comment' => 'State code, e.g. IN-MH'],
            'is_interstate'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_reverse_charge' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'seller_gstin'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'seller_name'       => ['type' => 'VARCHAR', 'constraint' => 200],
            'seller_address'    => ['type' => 'JSON'],
            'buyer_gstin'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'buyer_name'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'buyer_address'     => ['type' => 'JSON'],
            'shipping_address'  => ['type' => 'JSON', 'null' => true],
            'lines'             => ['type' => 'JSON', 'comment' => 'Frozen line items with HSN, qty, rate, taxable_value, CGST, SGST, IGST'],
            'taxable_amount'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'cgst_amount'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'sgst_amount'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'igst_amount'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'cess_amount'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'discount_amount'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'shipping_amount'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'rounding_adj'      => ['type' => 'INT', 'default' => 0],
            'total_amount'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'hsn_summary'       => ['type' => 'JSON', 'null' => true, 'comment' => 'HSN-wise tax summary for GSTR-1'],
            'pdf_path'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'irn'               => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'E-invoice IRN if applicable'],
            'qr_code'           => ['type' => 'TEXT', 'null' => true],
            'is_cancelled'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'cancelled_at'      => ['type' => 'DATETIME', 'null' => true],
            'generated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('invoice_number');
        $this->forge->addKey(['order_id', 'invoice_type']);
        $this->forge->addKey(['fy', 'invoice_date']);
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('invoices', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('invoices', true);
        $this->forge->dropTable('tax_rates', true);
        $this->db->query('ALTER TABLE products DROP FOREIGN KEY products_tax_fk');
        $this->forge->dropTable('tax_classes', true);
    }
}
