<?php
namespace App\Modules\Admin\Controllers;

use Config\Database;

class ReportsController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();
        $last30 = date('Y-m-d', strtotime('-30 days'));
        $sales = $db->table('orders')
            ->select('DATE(created_at) AS day, SUM(grand_total) AS revenue, COUNT(*) AS orders')
            ->where('created_at >=', $last30)
            ->whereNotIn('status', ['cancelled','failed'])
            ->groupBy('day')->orderBy('day','ASC')
            ->get()->getResultArray();
        $topProducts = $db->table('order_items oi')
            ->select('oi.product_id, SUM(oi.qty) AS qty, SUM(oi.line_total) AS revenue, p.name')
            ->join('products p', 'p.id = oi.product_id')
            ->groupBy('oi.product_id')->orderBy('qty','DESC')->limit(10)
            ->get()->getResultArray();
        $funnel = [
            'leads'        => $db->table('leads')->where('captured_at >=', $last30)->countAllResults(),
            'carts'        => $db->table('carts')->where('updated_at >=', $last30)->where('item_count >', 0)->countAllResults(),
            'checkouts'    => $db->table('checkout_sessions')->where('started_at >=', $last30)->countAllResults(),
            'orders'       => $db->table('orders')->where('created_at >=', $last30)->countAllResults(),
            'paid_orders'  => $db->table('orders')->where('created_at >=', $last30)->whereNotIn('status', ['cancelled','failed','pending_payment'])->countAllResults(),
        ];
        return $this->view('App\Modules\Admin\Views\reports', [
            'page' => ['title' => 'Reports'],
            'sales' => $sales, 'topProducts' => $topProducts, 'funnel' => $funnel,
        ]);
    }
    public function sales()    { return $this->index(); }
    public function products() { return $this->index(); }
    public function funnel()   { return $this->index(); }
}
