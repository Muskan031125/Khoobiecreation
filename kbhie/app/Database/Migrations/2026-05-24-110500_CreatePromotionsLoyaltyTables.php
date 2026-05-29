<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePromotionsLoyaltyTables extends Migration
{
    public function up()
    {
        // promotions — the rule engine
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 200],
            'description'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'type'                => ['type' => 'ENUM', 'constraint' => [
                'percent_off','flat_off','bogo','combo','tiered','free_shipping','free_gift','buy_x_get_y','cart_threshold'
            ]],
            'scope'               => ['type' => 'ENUM', 'constraint' => ['cart','product','category','customer_group','shipping'], 'default' => 'cart'],
            'priority'            => ['type' => 'INT', 'default' => 100],
            'rules'               => ['type' => 'JSON', 'null' => true, 'comment' => 'Conditions: {min_cart, contains_category, customer_segment, ...}'],
            'rewards'             => ['type' => 'JSON', 'null' => true, 'comment' => 'Actions: {type, value, target}'],
            'stackable'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'auto_apply'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => 'No coupon code needed'],
            'requires_coupon'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'max_uses'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'max_uses_per_user'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'used_count'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'starts_at'           => ['type' => 'DATETIME', 'null' => true],
            'ends_at'             => ['type' => 'DATETIME', 'null' => true],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'show_in_widget'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'banner_text'         => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['is_active', 'auto_apply', 'starts_at', 'ends_at']);
        $this->forge->addKey('type');
        $this->forge->createTable('promotions', true, ['ENGINE' => 'InnoDB']);

        // coupons — codes that trigger promotions
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 50],
            'promotion_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'max_uses'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'max_uses_per_user' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'used_count'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_single_use'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'restricted_to_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'starts_at'         => ['type' => 'DATETIME', 'null' => true],
            'ends_at'           => ['type' => 'DATETIME', 'null' => true],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('promotion_id');
        $this->forge->addForeignKey('promotion_id', 'promotions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('restricted_to_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('coupons', true, ['ENGINE' => 'InnoDB']);

        // promotion_usages — audit & max-uses enforcement
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'promotion_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'coupon_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'order_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'discount_amount' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['promotion_id', 'user_id']);
        $this->forge->addKey('coupon_id');
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('promotion_id', 'promotions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('coupon_id', 'coupons', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('promotion_usages', true, ['ENGINE' => 'InnoDB']);

        // loyalty_accounts — one per customer
        $this->forge->addField([
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'points_balance'  => ['type' => 'INT', 'default' => 0],
            'lifetime_points' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'tier'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'bronze'],
            'tier_expires_at' => ['type' => 'DATE', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('user_id');
        $this->forge->addKey('tier');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('loyalty_accounts', true, ['ENGINE' => 'InnoDB']);

        // loyalty_transactions — points ledger with expiry
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'points_change' => ['type' => 'INT'],
            'balance_after' => ['type' => 'INT', 'null' => true],
            'reason'        => ['type' => 'ENUM', 'constraint' => ['purchase','redemption','signup_bonus','referral','review','birthday','manual','expiry','refund']],
            'ref_type'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ref_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'note'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'expires_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addKey('expires_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('loyalty_transactions', true, ['ENGINE' => 'InnoDB']);

        // loyalty_rules — configurable earning rules
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event'          => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => 'signup, purchase, review, birthday, referral'],
            'description'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'points_formula' => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'e.g. "100" or "amount/10" or "qty*5"'],
            'expires_days'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('event');
        $this->forge->createTable('loyalty_rules', true, ['ENGINE' => 'InnoDB']);

        // referrals — refer-a-friend
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'referrer_user_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'referred_email'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'referred_phone'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'referred_user_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 30],
            'status'            => ['type' => 'ENUM', 'constraint' => ['pending','signed_up','purchased','rewarded','cancelled'], 'default' => 'pending'],
            'referrer_reward_at'=> ['type' => 'DATETIME', 'null' => true],
            'referred_reward_at'=> ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['referrer_user_id', 'status']);
        $this->forge->addKey('code');
        $this->forge->addForeignKey('referrer_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('referred_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('referrals', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('referrals', true);
        $this->forge->dropTable('loyalty_rules', true);
        $this->forge->dropTable('loyalty_transactions', true);
        $this->forge->dropTable('loyalty_accounts', true);
        $this->forge->dropTable('promotion_usages', true);
        $this->forge->dropTable('coupons', true);
        $this->forge->dropTable('promotions', true);
    }
}
