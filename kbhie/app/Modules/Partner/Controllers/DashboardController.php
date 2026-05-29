<?php

namespace App\Modules\Partner\Controllers;

use Config\Database;

class DashboardController extends BasePartnerController
{
    public function index()
    {
        $db = Database::connect();
        $stats = [];
        if ($this->partner) {
            $stats = [
                'orders_to_ship' => (int) $db->table('order_items')
                    ->where('partner_id', $this->partner['id'])
                    ->whereIn('fulfillment_status', ['pending', 'allocated', 'packed'])
                    ->countAllResults(),
                'orders_shipped' => (int) $db->table('order_items')
                    ->where('partner_id', $this->partner['id'])
                    ->where('fulfillment_status', 'shipped')
                    ->countAllResults(),
                'products_count' => (int) $db->table('products')
                    ->where('partner_id', $this->partner['id'])
                    ->where('status', 'active')
                    ->countAllResults(),
            ];
        }
        return $this->view('App\Modules\Partner\Views\dashboard', [
            'page'  => ['title' => 'Partner Dashboard'],
            'stats' => $stats,
        ]);
    }
}
