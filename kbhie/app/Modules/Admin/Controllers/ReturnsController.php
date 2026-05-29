<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Notifications\NotificationService;
use Config\Database;

class ReturnsController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();
        $status = $this->request->getGet('status') ?: '';
        $b = $db->table('returns r')
            ->select('r.*, o.order_number, o.name AS customer_name, o.email, o.phone, o.grand_total')
            ->join('orders o', 'o.id = r.order_id')
            ->orderBy('r.created_at', 'DESC');
        if ($status) $b->where('r.status', $status);
        $rows = $b->limit(200)->get()->getResultArray();

        $counts = [];
        foreach ($db->table('returns')->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }
        $counts['_total'] = (int) array_sum($counts);

        return $this->view('App\Modules\Admin\Views\returns_index', [
            'page'   => ['title' => 'Returns — Khoobie Admin'],
            'rows'   => $rows,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function show($id)
    {
        $db = Database::connect();
        $row = $db->table('returns r')
            ->select('r.*, o.order_number, o.name AS customer_name, o.email, o.phone, o.grand_total, o.shipping_address')
            ->join('orders o', 'o.id = r.order_id')
            ->where('r.id', (int) $id)->get()->getRowArray();
        if (! $row) return redirect()->to('/admin/returns');
        return $this->view('App\Modules\Admin\Views\returns_show', [
            'page' => ['title' => 'Return ' . $row['return_number']],
            'row'  => $row,
            'items' => json_decode($row['items'] ?? '[]', true) ?: [],
        ]);
    }

    public function approve($id)
    {
        $db = Database::connect();
        $refund = (int) round((float) $this->request->getPost('refund_inr') * 100);
        $note   = (string) $this->request->getPost('note');
        $row = $db->table('returns')->where('id', (int) $id)->get()->getRow();
        if (! $row) return redirect()->to('/admin/returns');

        $db->table('returns')->where('id', $id)->update([
            'status'        => 'approved',
            'refund_amount' => $refund,
            'description'   => trim($row->description . "\n\n[ADMIN NOTE]: " . $note),
        ]);
        // Notify customer
        try {
            $order = $db->table('orders')->where('id', $row->order_id)->get()->getRowArray();
            $payload = ['return_number' => $row->return_number, 'refund_amount' => $refund, 'order_number' => $order['order_number'], 'name' => $order['name']];
            $notif = new NotificationService();
            if ($order['email']) $notif->send('email',    $order['email'], 'return.approved', $payload, $order['user_id'], 'return', (int) $id);
            if ($order['phone']) $notif->send('whatsapp', $order['phone'], 'return.approved', $payload, $order['user_id'], 'return', (int) $id);
        } catch (\Throwable $e) {}
        return redirect()->to('/admin/returns/' . $id)->with('success', 'Return approved. Customer notified.');
    }

    public function reject($id)
    {
        $reason = (string) $this->request->getPost('reason');
        Database::connect()->table('returns')->where('id', (int) $id)->update([
            'status'      => 'rejected',
            'description' => trim('[REJECTED]: ' . $reason),
        ]);
        return redirect()->to('/admin/returns/' . $id)->with('success', 'Return rejected.');
    }

    public function markRefunded($id)
    {
        Database::connect()->table('returns')->where('id', (int) $id)->update([
            'status' => 'refunded',
        ]);
        return redirect()->to('/admin/returns/' . $id)->with('success', 'Marked refunded. Trigger gateway refund manually for now.');
    }
}
