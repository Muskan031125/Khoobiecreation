<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Expand product ecosystem beyond physical/digital/subscription:
 *  - Affiliate links (outbound to Amazon/Flipkart with click tracking)
 *  - Online courses (curriculum with modules + video lessons + progress)
 *  - Tuitions (recurring weekly classes)
 *  - Meetups (offline community events)
 *  - 1-on-1 services with calendar slots
 *  - Memberships (recurring access tier with perks)
 *
 * Each feature gets its own table; the products.type enum is extended so
 * the storefront and admin can render type-aware UI.
 */
class CreateEcosystemTables extends Migration
{
    public function up()
    {
        // -- Extend products.type to cover the full ecosystem
        $this->db->query("
            ALTER TABLE products MODIFY type ENUM(
                'simple','variable','bundle','digital','event','subscription',
                'affiliate','course','tuition','meetup','service','membership','workshop','camp','webinar'
            ) NOT NULL DEFAULT 'simple'
        ");

        // Optional kind on events to differentiate workshop / class / camp / webinar / meetup
        $this->db->query("
            ALTER TABLE events MODIFY type ENUM(
                'workshop','class','course','event','live_session','meetup','webinar','camp','tuition'
            ) NOT NULL DEFAULT 'workshop'
        ");

        // ============================================================
        // AFFILIATE
        // ============================================================
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'partner_name'    => ['type' => 'VARCHAR', 'constraint' => 100, 'comment' => 'Amazon / Flipkart / Myntra...'],
            'outbound_url'    => ['type' => 'VARCHAR', 'constraint' => 1000, 'comment' => 'Full URL with affiliate tag'],
            'partner_sku'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'commission_pct'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'commission_flat' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'price_at_partner'=> ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'paise — last seen price'],
            'price_updated_at'=> ['type' => 'DATETIME', 'null' => true],
            'click_count'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'conversion_count'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'lifetime_earnings'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        // NOTE: NOT unique — one product can list on multiple marketplaces
        // (Amazon + Flipkart + Meesho). PDP/card badges loop these and show
        // "Buy on Amazon · Buy on Flipkart" so customers see all options + prices.
        $this->forge->addKey('product_id');
        $this->forge->addKey('partner_name');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('affiliate_links', true, ['ENGINE' => 'InnoDB']);

        // ============================================================
        // COURSES (self-paced video curriculum)
        // ============================================================
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'instructor_name'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'instructor_bio'      => ['type' => 'TEXT', 'null' => true],
            'instructor_avatar'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'intro_video_url'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'language'            => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'English'],
            'level'               => ['type' => 'ENUM', 'constraint' => ['beginner','intermediate','advanced','all'], 'default' => 'beginner'],
            'total_minutes'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'lessons_count'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'what_youll_learn'    => ['type' => 'JSON', 'null' => true, 'comment' => 'Bullet list'],
            'prerequisites'       => ['type' => 'JSON', 'null' => true],
            'access_days'         => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL = lifetime'],
            'certificate_available'=> ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_drip'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => 'Release lessons over time'],
            'created_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'          => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('courses', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'description'=> ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['course_id', 'sort_order']);
        $this->forge->addForeignKey('course_id', 'courses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('course_modules', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'module_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'video_url'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'video_provider'   => ['type' => 'ENUM', 'constraint' => ['youtube','vimeo','mux','file','external'], 'default' => 'youtube'],
            'duration_minutes' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'content_html'     => ['type' => 'MEDIUMTEXT', 'null' => true, 'comment' => 'Lesson notes / transcript'],
            'attachments'      => ['type' => 'JSON', 'null' => true, 'comment' => 'PDFs / worksheets'],
            'is_preview'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => 'Free preview for non-enrolled'],
            'release_after_days'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'Drip: 0 = day 1'],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['module_id', 'sort_order']);
        $this->forge->addForeignKey('module_id', 'course_modules', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('course_lessons', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'course_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'order_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'enrolled_at'    => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'expires_at'     => ['type' => 'DATETIME', 'null' => true],
            'last_lesson_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'progress_pct'   => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'completed_at'   => ['type' => 'DATETIME', 'null' => true],
            'certificate_url'=> ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['course_id', 'user_id']);
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('course_id', 'courses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('course_enrollments', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'enrollment_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'lesson_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'watched_seconds'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'completed_at'   => ['type' => 'DATETIME', 'null' => true],
            'last_seen_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['enrollment_id', 'lesson_id']);
        $this->forge->addForeignKey('enrollment_id', 'course_enrollments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('lesson_id', 'course_lessons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('lesson_progress', true, ['ENGINE' => 'InnoDB']);

        // ============================================================
        // TUITIONS (recurring weekly classes)
        // ============================================================
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'subject'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'grade_level'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'instructor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'days_of_week'    => ['type' => 'JSON', 'comment' => '["Mon","Wed","Fri"]'],
            'start_time'      => ['type' => 'TIME'],
            'end_time'        => ['type' => 'TIME'],
            'timezone'        => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Asia/Kolkata'],
            'modality'        => ['type' => 'ENUM', 'constraint' => ['online','offline','hybrid'], 'default' => 'online'],
            'platform_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'venue_address'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'max_students'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => '0 = unlimited'],
            'trial_available' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'billing_cycle'   => ['type' => 'ENUM', 'constraint' => ['monthly','quarterly','term','annual'], 'default' => 'monthly'],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tuitions', true, ['ENGINE' => 'InnoDB']);

        // ============================================================
        // MEETUPS (offline / in-person events)
        // ============================================================
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'     => ['type' => 'BIGINT', 'unsigned' => true],
            'location_name'  => ['type' => 'VARCHAR', 'constraint' => 200],
            'address'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'city'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'state'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pincode'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'latitude'       => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'      => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'maps_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'starts_at'      => ['type' => 'DATETIME'],
            'ends_at'        => ['type' => 'DATETIME', 'null' => true],
            'capacity'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'rsvp_count'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_free'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'rsvp_required'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'cover_image'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'agenda'         => ['type' => 'JSON', 'null' => true, 'comment' => 'Time-slot agenda'],
            'host_name'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'host_phone'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['draft','published','full','cancelled','completed'], 'default' => 'draft'],
            'created_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'     => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('product_id');
        $this->forge->addKey(['city', 'starts_at']);
        $this->forge->addKey(['status', 'starts_at']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('meetups', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'meetup_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'phone'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'guests_count' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'note'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['rsvp','attended','no_show','cancelled'], 'default' => 'rsvp'],
            'rsvp_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['meetup_id', 'status']);
        $this->forge->addForeignKey('meetup_id', 'meetups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('meetup_rsvps', true, ['ENGINE' => 'InnoDB']);

        // ============================================================
        // 1-on-1 SERVICES (tutoring slots, parent consults, party planning)
        // ============================================================
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'service_kind'    => ['type' => 'ENUM', 'constraint' => ['tutoring','consultation','party_planning','toy_rental','custom'], 'default' => 'custom'],
            'provider_name'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'duration_minutes'=> ['type' => 'INT', 'unsigned' => true, 'default' => 60],
            'modality'        => ['type' => 'ENUM', 'constraint' => ['online','offline','at_home'], 'default' => 'online'],
            'platform_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('services', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'service_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'starts_at'  => ['type' => 'DATETIME'],
            'ends_at'    => ['type' => 'DATETIME'],
            'is_booked'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'booking_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['service_id', 'starts_at', 'is_booked']);
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('service_slots', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'service_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'slot_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'order_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'contact_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'contact_phone'=> ['type' => 'VARCHAR', 'constraint' => 20],
            'contact_email'=> ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'notes'        => ['type' => 'TEXT', 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['pending','confirmed','completed','cancelled','no_show'], 'default' => 'pending'],
            'created_at'   => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['service_id', 'status']);
        $this->forge->addKey('slot_id');
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('slot_id', 'service_slots', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('service_bookings', true, ['ENGINE' => 'InnoDB']);

        // ============================================================
        // MEMBERSHIPS (benefits-style; separate from subscription_plans which is product-billing)
        // ============================================================
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'tier_name'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'monthly_price'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'annual_price'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'paise'],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'perks'             => ['type' => 'JSON', 'null' => true, 'comment' => 'Bullet list of perks'],
            'discount_pct'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0, 'comment' => 'Discount on every order while active'],
            'free_shipping'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'early_access'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'free_courses'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'bonus_points_pct'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0, 'comment' => 'Multiplier on loyalty points earned'],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('memberships', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'membership_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'subscription_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'started_at'      => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'expires_at'      => ['type' => 'DATETIME', 'null' => true],
            'cancelled_at'    => ['type' => 'DATETIME', 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active','expired','cancelled','paused'], 'default' => 'active'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['membership_id', 'user_id']);
        $this->forge->addForeignKey('membership_id', 'memberships', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('subscription_id', 'subscriptions', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('membership_users', true, ['ENGINE' => 'InnoDB']);

        // ============================================================
        // PRE-ORDER / WAITLIST (for status='upcoming' products)
        // ============================================================
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'notified_at'=> ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['product_id', 'notified_at']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('product_waitlist', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        foreach ([
            'product_waitlist',
            'membership_users','memberships',
            'service_bookings','service_slots','services',
            'meetup_rsvps','meetups',
            'tuitions',
            'lesson_progress','course_enrollments','course_lessons','course_modules','courses',
            'affiliate_links',
        ] as $t) {
            $this->forge->dropTable($t, true);
        }
        // Note: ENUM ALTER not reverted (would require shrinking which would lose data on rows using new values)
    }
}
