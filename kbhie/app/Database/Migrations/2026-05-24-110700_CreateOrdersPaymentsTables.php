<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateOrdersPaymentsTables extends Migration
{
    public function up()
    {
        // orders
        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_number'          => ['type' => 'VARCHAR', 'constraint' => 30],
            'user_id'               => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'lead_id'               => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'customer_group_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status'                => ['type' => 'ENUM', 'constraint' => [
                'pending_payment','pending_confirmation','confirmed','processing','partially_shipped','shipped','out_for_delivery','delivered','cancelled','returned','refunded','failed'
            ], 'default' => 'pending_payment'],
            'confirmation_status'   => ['type' => 'ENUM', 'constraint' => ['not_required','pending','confirmed','unreachable','rejected'], 'default' => 'not_required'],
            'email'                 => ['type' => 'VARCHAR', 'constraint' => 191],
            'phone'                 => ['type' => 'VARCHAR', 'constraint' => 20],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 150],
            'shipping_address'      => ['type' => 'JSON'],
            'billing_address'       => ['type' => 'JSON', 'null' => true],
            'shipping_method'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'shipping_partner_pref' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'currency'              => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'INR'],
            'subtotal'              => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'discount_total'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'tax_total'             => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'shipping_total'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'cod_fee'               => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'grand_total'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'amount_paid'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'amount_due'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'loyalty_points_earned' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'loyalty_points_used'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'gift_card_amount'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'payment_method'        => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'payment_mode'          => ['type' => 'ENUM', 'constraint' => ['prepaid','cod','partial_cod','credit','gift_card','mixed'], 'null' => true],
            'source'                => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'web', 'comment' => 'web|instagram_dm|whatsapp|manual|api'],
            'utm_source'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'utm_medium'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'utm_campaign'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'affiliate_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'note_customer'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'note_internal'         => ['type' => 'TEXT', 'null' => true],
            'meta'                  => ['type' => 'JSON', 'null' => true],
            'placed_at'             => ['type' => 'DATETIME', 'null' => true],
            'confirmed_at'          => ['type' => 'DATETIME', 'null' => true],
            'shipped_at'            => ['type' => 'DATETIME', 'null' => true],
            'delivered_at'          => ['type' => 'DATETIME', 'null' => true],
            'cancelled_at'          => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'            => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('order_number');
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('phone');
        $this->forge->addKey('email');
        $this->forge->addKey('affiliate_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('customer_group_id', 'customer_groups', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('orders', true, ['ENGINE' => 'InnoDB']);

        // Add the FK from checkout_sessions to orders now that orders exists
        $this->db->query('ALTER TABLE checkout_sessions ADD CONSTRAINT checkout_sessions_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // order_items
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'            => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'partner_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'product_snapshot'    => ['type' => 'JSON', 'comment' => 'Frozen product/variant info at purchase time'],
            'qty'                 => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'unit_price'          => ['type' => 'INT', 'unsigned' => true],
            'line_discount'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'line_subtotal'       => ['type' => 'INT', 'unsigned' => true],
            'tax_rate'            => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'tax_amount'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'line_total'          => ['type' => 'INT', 'unsigned' => true],
            'fulfillment_status'  => ['type' => 'ENUM', 'constraint' => ['pending','allocated','packed','shipped','delivered','cancelled','returned'], 'default' => 'pending'],
            'is_digital'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'digital_asset_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'download_count'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'download_token'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'download_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'custom_fields'       => ['type' => 'JSON', 'null' => true],
            'bundle_parent_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'comment' => 'If this line is a child of a bundle'],
            'created_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('order_id');
        $this->forge->addKey('variant_id');
        $this->forge->addKey('partner_id');
        $this->forge->addKey(['fulfillment_status']);
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('digital_asset_id', 'digital_assets', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('order_items', true, ['ENGINE' => 'InnoDB']);

        // Now add FK from partner_payout_items to order_items
        $this->db->query('ALTER TABLE partner_payout_items ADD CONSTRAINT ppi_order_item_fk FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE ON UPDATE CASCADE');

        // order_status_history
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'from_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'to_status'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'changed_by'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'channel'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => 'web|admin|system|webhook'],
            'note'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['order_id', 'created_at']);
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('order_status_history', true, ['ENGINE' => 'InnoDB']);

        // order_confirmations — manual phone/email/whatsapp confirmation flow
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'channel'        => ['type' => 'ENUM', 'constraint' => ['phone','email','whatsapp','sms']],
            'agent_user_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'attempted_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'confirmed_at'   => ['type' => 'DATETIME', 'null' => true],
            'outcome'        => ['type' => 'ENUM', 'constraint' => ['confirmed','no_answer','rejected','reschedule','wrong_number','address_correction'], 'null' => true],
            'response_note'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['order_id', 'attempted_at']);
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agent_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('order_confirmations', true, ['ENGINE' => 'InnoDB']);

        // payments
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'            => ['type' => 'BIGINT', 'unsigned' => true],
            'gateway'             => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => 'razorpay|phonepe|cod|upi_manual|gift_card|wallet|credit'],
            'gateway_order_id'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'gateway_txn_id'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'gateway_payment_id'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'amount'              => ['type' => 'INT', 'unsigned' => true],
            'currency'            => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'INR'],
            'status'              => ['type' => 'ENUM', 'constraint' => ['initiated','pending','captured','failed','refunded','partial_refunded','cancelled'], 'default' => 'initiated'],
            'method_detail'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'upi/card_visa/netbanking_hdfc/etc'],
            'is_advance'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => 'Partial-COD advance flag'],
            'raw_request'         => ['type' => 'JSON', 'null' => true],
            'raw_response'        => ['type' => 'JSON', 'null' => true],
            'error_code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'error_message'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'initiated_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'paid_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['order_id', 'status']);
        $this->forge->addKey('gateway_order_id');
        $this->forge->addKey('gateway_payment_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payments', true, ['ENGINE' => 'InnoDB']);

        // payment_refunds
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'payment_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'order_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'amount'            => ['type' => 'INT', 'unsigned' => true],
            'reason'            => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'gateway_refund_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['pending','processed','failed'], 'default' => 'pending'],
            'raw_response'      => ['type' => 'JSON', 'null' => true],
            'refunded_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['payment_id', 'status']);
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payment_refunds', true, ['ENGINE' => 'InnoDB']);

        // shipments — one order can have many (multi-vendor split)
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'partner_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'warehouse_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'courier'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'awb'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tracking_url'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['pending','packed','manifested','picked_up','in_transit','out_for_delivery','delivered','rto_in_transit','rto_delivered','lost','damaged','cancelled'], 'default' => 'pending'],
            'weight_g'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'shipping_cost'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'manifested_at'  => ['type' => 'DATETIME', 'null' => true],
            'picked_at'      => ['type' => 'DATETIME', 'null' => true],
            'shipped_at'     => ['type' => 'DATETIME', 'null' => true],
            'delivered_at'   => ['type' => 'DATETIME', 'null' => true],
            'meta'           => ['type' => 'JSON', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['order_id', 'status']);
        $this->forge->addKey('awb');
        $this->forge->addKey('partner_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('shipments', true, ['ENGINE' => 'InnoDB']);

        // shipment_items
        $this->forge->addField([
            'shipment_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'order_item_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'qty'           => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey(['shipment_id', 'order_item_id']);
        $this->forge->addKey('order_item_id');
        $this->forge->addForeignKey('shipment_id', 'shipments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_item_id', 'order_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('shipment_items', true, ['ENGINE' => 'InnoDB']);

        // shipment_tracking_events
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'shipment_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'location'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'occurred_at' => ['type' => 'DATETIME'],
            'raw'         => ['type' => 'JSON', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['shipment_id', 'occurred_at']);
        $this->forge->addForeignKey('shipment_id', 'shipments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('shipment_tracking_events', true, ['ENGINE' => 'InnoDB']);

        // returns — RMA
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'return_number'     => ['type' => 'VARCHAR', 'constraint' => 30],
            'type'              => ['type' => 'ENUM', 'constraint' => ['return','exchange'], 'default' => 'return'],
            'reason'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['requested','approved','rejected','picked_up','received','refunded','exchanged','cancelled'], 'default' => 'requested'],
            'refund_amount'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'restocking_fee'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'pickup_address'    => ['type' => 'JSON', 'null' => true],
            'items'             => ['type' => 'JSON', 'null' => true, 'comment' => 'Which order_items + qty'],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('return_number');
        $this->forge->addKey(['order_id', 'status']);
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('returns', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE checkout_sessions DROP FOREIGN KEY checkout_sessions_order_fk');
        $this->db->query('ALTER TABLE partner_payout_items DROP FOREIGN KEY ppi_order_item_fk');
        $this->forge->dropTable('returns', true);
        $this->forge->dropTable('shipment_tracking_events', true);
        $this->forge->dropTable('shipment_items', true);
        $this->forge->dropTable('shipments', true);
        $this->forge->dropTable('payment_refunds', true);
        $this->forge->dropTable('payments', true);
        $this->forge->dropTable('order_confirmations', true);
        $this->forge->dropTable('order_status_history', true);
        $this->forge->dropTable('order_items', true);
        $this->forge->dropTable('orders', true);
    }
}
