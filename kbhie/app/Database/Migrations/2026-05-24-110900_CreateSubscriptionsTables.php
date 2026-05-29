<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSubscriptionsTables extends Migration
{
    public function up()
    {
        // subscription_plans — e.g. "Monthly Khoobie Box"
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'billing_interval'  => ['type' => 'ENUM', 'constraint' => ['day','week','month','year'], 'default' => 'month'],
            'interval_count'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'price'             => ['type' => 'INT', 'unsigned' => true, 'comment' => 'paise per cycle'],
            'setup_fee'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'trial_days'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'max_billing_cycles'=> ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL = forever'],
            'box_contents'      => ['type' => 'JSON', 'null' => true, 'comment' => 'For monthly box products'],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('subscription_plans', true, ['ENGINE' => 'InnoDB']);

        // subscriptions — active customer subs
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'             => ['type' => 'BIGINT', 'unsigned' => true],
            'plan_id'             => ['type' => 'BIGINT', 'unsigned' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['trialing','active','past_due','paused','cancelled','completed','failed'], 'default' => 'active'],
            'gateway'             => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'gateway_subscription_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'shipping_address'    => ['type' => 'JSON', 'null' => true],
            'billing_cycles_completed' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'started_at'          => ['type' => 'DATETIME'],
            'trial_ends_at'       => ['type' => 'DATETIME', 'null' => true],
            'next_billing_at'     => ['type' => 'DATETIME', 'null' => true],
            'paused_until'        => ['type' => 'DATETIME', 'null' => true],
            'cancelled_at'        => ['type' => 'DATETIME', 'null' => true],
            'cancel_reason'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addKey('next_billing_at');
        $this->forge->addKey('gateway_subscription_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('plan_id', 'subscription_plans', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('subscriptions', true, ['ENGINE' => 'InnoDB']);

        // subscription_orders — each billing cycle = one order
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'subscription_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'order_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'billing_cycle'   => ['type' => 'INT', 'unsigned' => true],
            'billed_amount'   => ['type' => 'INT', 'unsigned' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['scheduled','processing','success','failed','skipped'], 'default' => 'scheduled'],
            'attempted_at'    => ['type' => 'DATETIME', 'null' => true],
            'succeeded_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['subscription_id', 'billing_cycle']);
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('subscription_id', 'subscriptions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('subscription_orders', true, ['ENGINE' => 'InnoDB']);

        // subscription_pauses — pause history
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'subscription_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'paused_at'       => ['type' => 'DATETIME'],
            'resumed_at'      => ['type' => 'DATETIME', 'null' => true],
            'reason'          => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'created_by'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('subscription_id');
        $this->forge->addForeignKey('subscription_id', 'subscriptions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('subscription_pauses', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('subscription_pauses', true);
        $this->forge->dropTable('subscription_orders', true);
        $this->forge->dropTable('subscriptions', true);
        $this->forge->dropTable('subscription_plans', true);
    }
}
