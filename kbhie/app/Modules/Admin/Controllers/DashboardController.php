<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

class DashboardController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();
        $today      = date('Y-m-d');
        $weekStart  = date('Y-m-d', strtotime('-6 days'));
        $monthStart = date('Y-m-01');

        // Revenue tiles
        $rev = [
            'today'     => $this->revenue($db, "DATE(created_at) = '{$today}'"),
            'week'      => $this->revenue($db, "DATE(created_at) >= '{$weekStart}'"),
            'month'     => $this->revenue($db, "DATE(created_at) >= '{$monthStart}'"),
            'lifetime'  => $this->revenue($db, '1=1'),
        ];

        // Order status mix
        $statusBreakdown = $db->table('orders')
            ->select('status, COUNT(*) AS n')
            ->groupBy('status')->orderBy('n', 'DESC')
            ->get()->getResultArray();

        // Last 7 days daily revenue (for sparkline)
        $sparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $sparkline[] = [
                'day'     => date('D', strtotime($d)),
                'date'    => $d,
                'revenue' => $this->revenue($db, "DATE(created_at) = '{$d}'"),
            ];
        }

        // Top selling products this month
        $topProducts = $db->table('order_items oi')
            ->select('p.name, p.slug, COUNT(oi.id) AS units, SUM(oi.line_total) AS revenue')
            ->join('products p', 'p.id = oi.product_id')
            ->join('orders o',   'o.id = oi.order_id')
            ->where("DATE(o.created_at) >=", $monthStart)
            ->groupBy('p.id')
            ->orderBy('revenue', 'DESC')
            ->limit(5)->get()->getResultArray();

        // Intent funnel (last 30 days)
        $intentFunnel = [
            'captured'    => (int) $db->table('intents')->where("DATE(created_at) >=", date('Y-m-d', strtotime('-30 days')))->countAllResults(),
            'verified'    => (int) $db->table('intents')->where("DATE(created_at) >=", date('Y-m-d', strtotime('-30 days')))->where('verified_at IS NOT NULL', null, false)->countAllResults(),
            'reserved'    => (int) $db->table('intents')->where("DATE(created_at) >=", date('Y-m-d', strtotime('-30 days')))->where('status', 'reserved')->countAllResults(),
            'converted'   => (int) $db->table('intents')->where("DATE(created_at) >=", date('Y-m-d', strtotime('-30 days')))->where('status', 'converted')->countAllResults(),
        ];

        // Referral conversions this month — schema uses status + referrer_reward_at,
        // not signed_up_at / rewarded_at. Points are computed at reward time (100 per
        // rewarded conversion is the current ReferralService rule).
        $refStats = [
            'signups'   => (int) $db->table('referrals')
                ->whereIn('status', ['signed_up','purchased','rewarded'])
                ->where("DATE(created_at) >=", $monthStart)
                ->countAllResults(),
            'rewarded'  => (int) $db->table('referrals')
                ->where('status', 'rewarded')
                ->where('referrer_reward_at IS NOT NULL', null, false)
                ->where("DATE(referrer_reward_at) >=", $monthStart)
                ->countAllResults(),
            'points'    => 0, // populated below
        ];
        // 100 points per rewarded referrer + 100 per rewarded referred user (mirrors ReferralService)
        $refStats['points'] = $refStats['rewarded'] * 100
            + (int) $db->table('referrals')
                ->where('status', 'rewarded')
                ->where('referred_reward_at IS NOT NULL', null, false)
                ->where("DATE(referred_reward_at) >=", $monthStart)
                ->countAllResults() * 100;

        // Recent leads (last 10 intents needing follow-up)
        $recentLeads = $db->table('intents i')
            ->select('i.id, i.kind, i.name, i.phone, i.email, i.status, i.created_at, p.name AS product_name, p.slug')
            ->join('products p', 'p.id = i.product_id', 'left')
            ->whereIn('i.status', ['verified','pending','reserved'])
            ->orderBy('i.id', 'DESC')->limit(10)->get()->getResultArray();

        // Low stock alerts
        $lowStock = $db->table('inventory i')
            ->select('p.name, p.slug, v.sku, i.qty_on_hand')
            ->join('product_variants v', 'v.id = i.variant_id')
            ->join('products p', 'p.id = v.product_id')
            ->where('i.qty_on_hand <=', 5)
            ->where('v.is_active', 1)->where('p.status', 'active')
            ->orderBy('i.qty_on_hand', 'ASC')
            ->limit(8)->get()->getResultArray();

        // Top traffic sources from attribution JSON (last 30 days of orders)
        $sources = $db->query("SELECT JSON_EXTRACT(attribution, '$.source') AS s, COUNT(*) AS n
                               FROM orders WHERE DATE(created_at) >= ? AND attribution IS NOT NULL
                               GROUP BY s ORDER BY n DESC LIMIT 6", [$monthStart])->getResultArray();
        foreach ($sources as &$s) {
            $s['s'] = trim((string) $s['s'], '"') ?: 'direct';
        }

        return $this->view('App\Modules\Admin\Views\dashboard', [
            'page'            => ['title' => 'Dashboard — Khoobie Admin'],
            'rev'             => $rev,
            'statusBreakdown' => $statusBreakdown,
            'sparkline'       => $sparkline,
            'topProducts'     => $topProducts,
            'intentFunnel'    => $intentFunnel,
            'refStats'        => $refStats,
            'recentLeads'     => $recentLeads,
            'lowStock'        => $lowStock,
            'sources'         => $sources,
            'counts'          => [
                'products_active' => (int) $db->table('products')->where('status','active')->countAllResults(),
                'customers'       => (int) $db->table('users u')->join('user_roles ur','ur.user_id=u.id')->join('roles r','r.id=ur.role_id')->where('r.name','customer')->countAllResults(),
                'orders_pending'  => (int) $db->table('orders')->whereIn('status', ['pending_confirmation','pending_payment'])->countAllResults(),
                'blog_published'  => (int) $db->table('blogs')->where('status','published')->countAllResults(),
            ],
        ]);
    }

    private function revenue($db, string $where): int
    {
        return (int) ($db->query("SELECT COALESCE(SUM(grand_total), 0) AS s FROM orders WHERE {$where} AND status NOT IN ('cancelled','failed')")->getRow()->s ?? 0);
    }
}
