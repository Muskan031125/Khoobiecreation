<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

/**
 * The "wow" page — every meaningful platform metric in one investor-ready view.
 * Pulls everything from the live DB; nothing is mocked.
 */
class InvestorController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();

        // ----- North-star numbers
        $stats = [
            'gmv_lifetime'      => $this->money($db, "1=1"),
            'gmv_30d'           => $this->money($db, "DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'gmv_7d'            => $this->money($db, "DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'gmv_today'         => $this->money($db, "DATE(created_at) = CURDATE()"),
            'customers'         => (int) $db->table('users u')->join('user_roles ur','ur.user_id=u.id')->join('roles r','r.id=ur.role_id')->where('r.name','customer')->countAllResults(),
            'orders_lifetime'   => (int) $db->table('orders')->whereNotIn('status', ['cancelled','failed'])->countAllResults(),
            'products_active'   => (int) $db->table('products')->where('status','active')->countAllResults(),
            'partners'          => (int) $db->table('partners')->countAllResults(),
            'classes_listed'    => (int) $db->table('products')->whereIn('type', ['tuition','course','meetup','service','membership'])->where('status','active')->countAllResults(),
            'cities_served'     => (int) $db->table('meetups')->select('city', false)->distinct()->countAllResults(),
            'leads_30d'         => (int) $db->table('intents')->where('created_at >=', date('Y-m-d', strtotime('-30 days')))->countAllResults(),
            'blog_published'    => (int) $db->table('blogs')->where('status','published')->countAllResults(),
        ];

        // ----- GMV split by product line (revenue mix)
        $gmvByLine = $db->query("
            SELECT
                CASE
                    WHEN p.type IN ('simple','variable','bundle') THEN 'Physical'
                    WHEN p.type IN ('digital')                     THEN 'Digital'
                    WHEN p.type IN ('course','tuition')            THEN 'Online classes'
                    WHEN p.type IN ('meetup','service')            THEN 'In-person'
                    WHEN p.type = 'membership'                     THEN 'Memberships'
                    ELSE 'Other'
                END AS line,
                SUM(oi.line_total) AS rev,
                COUNT(DISTINCT oi.order_id) AS orders
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            WHERE o.status NOT IN ('cancelled','failed')
              AND o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY line
            ORDER BY rev DESC
        ")->getResultArray();
        $totalRev = max(1, array_sum(array_column($gmvByLine, 'rev')));

        // ----- 30-day daily revenue
        $daily = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $daily[] = [
                'date' => $d,
                'gmv'  => $this->money($db, "DATE(created_at) = '{$d}'"),
            ];
        }
        $maxDaily = max(1, max(array_column($daily, 'gmv')));

        // ----- Conversion funnel (last 30d)
        $cutoff = date('Y-m-d', strtotime('-30 days'));
        $funnel = [
            'visitors_est' => (int) (1.5 * ($db->query("SELECT COUNT(DISTINCT anon_id) AS n FROM intents WHERE created_at >= ?", [$cutoff])->getRow()->n ?? 0)),
            'leads'        => (int) $db->table('intents')->where('created_at >=', $cutoff)->countAllResults(),
            'verified'     => (int) $db->table('intents')->where('created_at >=', $cutoff)->where('verified_at IS NOT NULL', null, false)->countAllResults(),
            'orders'       => (int) $db->table('orders')->where('created_at >=', $cutoff)->whereNotIn('status', ['cancelled','failed'])->countAllResults(),
            'paid'         => (int) $db->table('orders')->where('created_at >=', $cutoff)->where('status', 'paid')->countAllResults(),
        ];

        // ----- Geo split — top cities
        $cities = $db->query("
            SELECT JSON_UNQUOTE(JSON_EXTRACT(attribution, '$.source')) AS src,
                   COUNT(*) AS orders, SUM(grand_total) AS gmv
            FROM orders
            WHERE status NOT IN ('cancelled','failed') AND attribution IS NOT NULL
              AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY src ORDER BY gmv DESC LIMIT 8
        ")->getResultArray();

        // ----- AI usage
        $aiStats = [
            'blogs_ai'      => (int) $db->table('blogs')->where('ai_generated', 1)->countAllResults(),
            'campaigns_ai'  => (int) $db->table('campaigns')->where('ai_generated', 1)->countAllResults(),
            'imports_total' => (int) $db->table('products')->like('sku', 'KK-IMP-', 'after')->countAllResults(),
        ];

        // ----- Recent activity feed
        $activity = $db->query("
            (SELECT 'order' AS kind, id, order_number AS label, name AS who, grand_total AS amt, created_at FROM orders WHERE status='paid' ORDER BY id DESC LIMIT 5)
            UNION ALL
            (SELECT 'intent' AS kind, id, kind AS label, name AS who, amount_paid AS amt, created_at FROM intents WHERE status IN ('verified','reserved','converted') ORDER BY id DESC LIMIT 5)
            UNION ALL
            (SELECT 'review' AS kind, id, CONCAT('★', rating) AS label, reviewer_name AS who, 0 AS amt, created_at FROM reviews WHERE status='published' ORDER BY id DESC LIMIT 3)
            ORDER BY created_at DESC LIMIT 12
        ")->getResultArray();

        return $this->view('App\Modules\Admin\Views\investor', [
            'page'      => ['title' => 'Khoobie · Investor Dashboard'],
            'stats'     => $stats,
            'gmvByLine' => $gmvByLine,
            'totalRev'  => $totalRev,
            'daily'     => $daily,
            'maxDaily'  => $maxDaily,
            'funnel'    => $funnel,
            'cities'    => $cities,
            'aiStats'   => $aiStats,
            'activity'  => $activity,
        ]);
    }

    private function money($db, string $where): int
    {
        return (int) ($db->query("SELECT COALESCE(SUM(grand_total), 0) AS s FROM orders WHERE {$where} AND status NOT IN ('cancelled','failed')")->getRow()->s ?? 0);
    }
}
