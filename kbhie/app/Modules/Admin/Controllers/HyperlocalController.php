<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

class HyperlocalController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();

        // Latest snapshot
        $latest = $db->table('hyperlocal_demand_snapshots')->orderBy('id', 'DESC')->limit(1)->get()->getRow();
        $gaps = $latest ? (json_decode($latest->gaps_json, true) ?: []) : [];

        // Live current-state aggregation (for ops without waiting for cron)
        $cutoff = date('Y-m-d H:i:s', strtotime('-30 days'));
        $live = $db->query("
            SELECT m.city, m.locality, COUNT(DISTINCT i.id) AS intents_30d,
                   COUNT(DISTINCT p2.id) AS supply_n
            FROM meetups m
            LEFT JOIN products p2 ON p2.id = m.product_id AND p2.status = 'active'
            LEFT JOIN intents i ON i.product_id = p2.id AND i.created_at >= ?
            GROUP BY m.city, m.locality
            ORDER BY intents_30d DESC, m.city, m.locality
            LIMIT 200
        ", [$cutoff])->getResultArray();

        // City rollup
        $cityRollup = $db->query("
            SELECT m.city, COUNT(DISTINCT p2.id) AS supply,
                   COUNT(DISTINCT i.id) AS intents_30d,
                   COUNT(DISTINCT DATE(i.created_at)) AS active_days
            FROM meetups m
            LEFT JOIN products p2 ON p2.id = m.product_id AND p2.status = 'active'
            LEFT JOIN intents i ON i.product_id = p2.id AND i.created_at >= ?
            GROUP BY m.city
            ORDER BY intents_30d DESC, supply DESC
        ", [$cutoff])->getResultArray();

        return $this->view('App\Modules\Admin\Views\hyperlocal_index', [
            'page'        => ['title' => 'Hyperlocal Demand'],
            'latest'      => $latest,
            'gaps'        => $gaps,
            'live'        => $live,
            'cityRollup'  => $cityRollup,
        ]);
    }
}
