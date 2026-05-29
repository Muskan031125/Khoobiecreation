<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Gst\InvoiceService;
use App\Libraries\Notifications\NotificationService;
use Config\Database;

class OrdersController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();
        $req = $this->request;
        $q       = trim((string) $req->getGet('q'));
        $status  = $req->getGet('status') ?: '';
        $page    = max(1, (int) $req->getGet('page'));
        $perPage = (int) ($req->getGet('per_page') ?? 25);
        if (! in_array($perPage, [25, 50, 100, 250], true)) $perPage = 25;

        $sort    = $req->getGet('sort') ?: 'created_at';
        $sortDir = strtoupper($req->getGet('dir') ?: 'DESC');
        if (! in_array($sortDir, ['ASC','DESC'], true)) $sortDir = 'DESC';
        $allowedSort = ['id','order_number','created_at','grand_total','status','name'];
        if (! in_array($sort, $allowedSort, true)) $sort = 'created_at';

        $b = $db->table('orders');
        if ($status) $b->where('status', $status);
        if ($q)      $b->groupStart()->like('order_number', $q)->orLike('email', $q)->orLike('phone', $q)->orLike('name', $q)->groupEnd();
        $total = (int) $b->countAllResults(false);
        $orders = $b->orderBy($sort, $sortDir)
                    ->limit($perPage, ($page - 1) * $perPage)
                    ->get()->getResultArray();

        return $this->view('App\Modules\Admin\Views\orders_index', [
            'page'           => ['title' => 'Orders'],
            'orders'         => $orders,
            'status'         => $status,
            'q'              => $q,
            'sort'           => $sort,
            'sortDir'        => $sortDir,
            'currentPage'    => $page,
            'perPage'        => $perPage,
            'perPageOptions' => [25, 50, 100, 250],
            'totalRows'      => $total,
            'totalPages'     => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function show($id) { return $this->detail($id); }
    public function edit($id) { return $this->detail($id); }
    public function new()     { return redirect()->to('/admin/orders'); }
    public function create()  { return redirect()->to('/admin/orders'); }
    public function update($id) { return redirect()->to('/admin/orders/' . $id); }
    public function delete($id) { return redirect()->to('/admin/orders'); }

    public function detail($id)
    {
        $db = Database::connect();
        $order = $db->table('orders')->where('id', $id)->get()->getRowArray();
        if (! $order) return redirect()->to('/admin/orders');
        $items = $db->table('order_items')->where('order_id', $id)->get()->getResultArray();
        $payments = $db->table('payments')->where('order_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        $history = $db->table('order_status_history')->where('order_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        $confirmations = $db->table('order_confirmations')->where('order_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        $shipments = $db->table('shipments')->where('order_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        return $this->view('App\Modules\Admin\Views\orders_detail', [
            'page' => ['title' => 'Order #' . $order['order_number']],
            'order' => $order, 'items' => $items, 'payments' => $payments,
            'history' => $history, 'confirmations' => $confirmations, 'shipments' => $shipments,
        ]);
    }

    public function confirm($id)
    {
        $db = Database::connect();
        $order = $db->table('orders')->where('id', $id)->get()->getRowArray();
        if (! $order) return redirect()->to('/admin/orders');
        $channel = $this->request->getPost('channel') ?: 'phone';
        $note    = $this->request->getPost('note') ?: '';
        $db->table('order_confirmations')->insert([
            'order_id'      => $id,
            'channel'       => $channel,
            'agent_user_id' => session('user')['id'] ?? null,
            'attempted_at'  => date('Y-m-d H:i:s'),
            'confirmed_at'  => date('Y-m-d H:i:s'),
            'outcome'       => 'confirmed',
            'response_note' => $note,
        ]);
        $newStatus = $order['payment_method'] === 'cod' ? 'processing' : 'processing';
        $db->table('orders')->where('id', $id)->update([
            'status'              => $newStatus,
            'confirmation_status' => 'confirmed',
            'confirmed_at'        => date('Y-m-d H:i:s'),
        ]);
        $db->table('order_status_history')->insert([
            'order_id' => $id, 'from_status' => $order['status'], 'to_status' => $newStatus,
            'changed_by' => session('user')['id'] ?? null, 'channel' => 'admin',
            'note' => 'Confirmed via ' . $channel . ($note ? ': ' . $note : ''),
        ]);
        // Generate GST invoice + fire confirmation notifications
        try {
            (new InvoiceService())->generateForOrder((int) $id);
            $payload = ['order_number' => $order['order_number'], 'name' => $order['name']];
            $notif = new NotificationService();
            if ($order['email']) $notif->send('email',    $order['email'], 'order.confirmed', $payload, $order['user_id'], 'order', (int) $id);
            if ($order['phone']) $notif->send('whatsapp', $order['phone'], 'order.confirmed', $payload, $order['user_id'], 'order', (int) $id);
        } catch (\Throwable $e) { log_message('error', 'Confirm side-effects failed: ' . $e->getMessage()); }

        return redirect()->to('/admin/orders/' . $id)->with('success', 'Order confirmed.');
    }

    /**
     * Capture-balance for partial_venue + partial_cod orders.
     * Called by the instructor (or admin) when the customer pays the balance
     * in cash/UPI at the venue or to the courier.
     */
    public function captureBalance($id)
    {
        $db = Database::connect();
        $order = $db->table('orders')->where('id', $id)->get()->getRowArray();
        if (! $order) return redirect()->to('/admin/orders');

        if (! in_array($order['payment_mode'] ?? '', ['partial_venue','partial_cod','cod'], true)) {
            return redirect()->back()->with('error', 'This order has no balance to capture.');
        }

        $method = $this->request->getPost('method') ?: 'cash';   // cash / upi / other
        $note   = (string) $this->request->getPost('note');
        $amount = (int) $order['amount_due'];

        $balanceGateway = ($order['payment_mode'] === 'partial_venue') ? 'at_venue' : 'cod';
        $pending = $db->table('payments')
            ->where('order_id', $id)->where('gateway', $balanceGateway)
            ->where('status', 'pending')->orderBy('id','ASC')->get()->getRow();

        $db->transStart();
        if ($pending) {
            $db->table('payments')->where('id', $pending->id)->update([
                'status'         => 'captured',
                'paid_at'        => date('Y-m-d H:i:s'),
                'method_detail'  => $method,
                'gateway_payment_id' => $note ?: 'manual',
            ]);
        } else {
            // Defensive: row missing — create one
            $db->table('payments')->insert([
                'order_id' => $id, 'gateway' => $balanceGateway, 'amount' => $amount,
                'status' => 'captured', 'paid_at' => date('Y-m-d H:i:s'),
                'method_detail' => $method, 'gateway_payment_id' => $note ?: 'manual',
            ]);
        }
        $db->table('orders')->where('id', $id)->update([
            'amount_paid' => (int) $order['amount_paid'] + $amount,
            'amount_due'  => 0,
            'status'      => 'paid',
            'paid_at'     => date('Y-m-d H:i:s'),
        ]);
        $db->table('order_status_history')->insert([
            'order_id'   => $id, 'from_status' => $order['status'], 'to_status' => 'paid',
            'changed_by' => session('user')['id'] ?? null, 'channel' => 'admin',
            'note'       => "Balance ₹" . number_format(round($amount/100)) . " captured via {$method}" . ($note ? ": {$note}" : ''),
        ]);
        $db->transComplete();

        // Trigger referral reward if this is the customer's first paid order
        if ($order['user_id']) {
            try { (new \App\Libraries\ReferralService())->rewardOnFirstOrder((int) $order['user_id'], (int) $id, (int) $order['grand_total']); }
            catch (\Throwable $e) { log_message('error', 'Referral reward failed: ' . $e->getMessage()); }
        }

        return redirect()->to('/admin/orders/' . $id)->with('success', '✓ Balance captured — order marked paid.');
    }

    public function cancel($id)
    {
        $db = Database::connect();
        $order = $db->table('orders')->where('id', $id)->get()->getRowArray();
        if (! $order) return redirect()->to('/admin/orders');
        $db->table('orders')->where('id', $id)->update([
            'status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('order_status_history')->insert([
            'order_id' => $id, 'from_status' => $order['status'], 'to_status' => 'cancelled',
            'changed_by' => session('user')['id'] ?? null, 'channel' => 'admin',
            'note' => $this->request->getPost('reason') ?: 'Cancelled by admin',
        ]);
        return redirect()->to('/admin/orders/' . $id)->with('success', 'Order cancelled.');
    }
}
