<?php

namespace App\Modules\Partner\Controllers;

use App\Libraries\Notifications\NotificationService;
use App\Libraries\Shipping\ShiprocketService;
use Config\Database;

class OrdersController extends BasePartnerController
{
    public function index()
    {
        if (! $this->partner) return redirect()->to('/partner/login');
        $db = Database::connect();
        $status = $this->request->getGet('status');
        $b = $db->table('order_items oi')
            ->select('oi.*, o.order_number, o.name AS buyer_name, o.phone AS buyer_phone, o.shipping_address, o.status AS order_status, o.created_at AS placed_at')
            ->join('orders o', 'o.id = oi.order_id')
            ->where('oi.partner_id', $this->partner['id'])
            ->whereNotIn('o.status', ['cancelled','failed','pending_payment'])
            ->orderBy('o.created_at', 'DESC');
        if ($status) $b->where('oi.fulfillment_status', $status);
        $items = $b->limit(200)->get()->getResultArray();
        return $this->view('App\Modules\Partner\Views\orders_index', [
            'page' => ['title' => 'Orders to fulfill'],
            'items' => $items,
            'status' => $status,
        ]);
    }

    public function detail($id)
    {
        $db = Database::connect();
        $order = $db->table('orders')->where('id', $id)->get()->getRowArray();
        if (! $order) return redirect()->to('/partner/orders');
        $items = $db->table('order_items')->where('order_id', $id)->where('partner_id', $this->partner['id'])->get()->getResultArray();
        if (empty($items)) return redirect()->to('/partner/orders');
        $shipment = $db->table('shipments')->where('order_id', $id)->where('partner_id', $this->partner['id'])->orderBy('id','DESC')->limit(1)->get()->getRowArray();
        return $this->view('App\Modules\Partner\Views\order_detail', [
            'page' => ['title' => 'Order #' . $order['order_number']],
            'order' => $order, 'items' => $items, 'shipment' => $shipment,
        ]);
    }

    public function ship($id)
    {
        $db = Database::connect();
        // Mark items packed -> shipped, create shipment record
        $items = $db->table('order_items')->where('order_id', $id)->where('partner_id', $this->partner['id'])->get()->getResultArray();
        if (empty($items)) return redirect()->to('/partner/orders');

        $courier = $this->request->getPost('courier') ?: 'manual';
        $awb     = $this->request->getPost('awb');

        $db->table('shipments')->insert([
            'order_id'   => $id,
            'partner_id' => $this->partner['id'],
            'courier'    => $courier,
            'awb'        => $awb,
            'status'     => 'shipped',
            'shipped_at' => date('Y-m-d H:i:s'),
        ]);
        $shipmentId = (int) $db->insertID();
        foreach ($items as $it) {
            $db->table('shipment_items')->insert(['shipment_id' => $shipmentId, 'order_item_id' => $it['id'], 'qty' => $it['qty']]);
            $db->table('order_items')->where('id', $it['id'])->update(['fulfillment_status' => 'shipped']);
        }
        // Update parent order status if everything shipped
        $remaining = $db->table('order_items')->where('order_id', $id)->whereNotIn('fulfillment_status', ['shipped','delivered','cancelled','returned'])->countAllResults();
        $newStatus = $remaining === 0 ? 'shipped' : 'partially_shipped';
        $db->table('orders')->where('id', $id)->update(['status' => $newStatus, 'shipped_at' => $remaining === 0 ? date('Y-m-d H:i:s') : null]);
        $db->table('order_status_history')->insert([
            'order_id' => $id, 'to_status' => $newStatus, 'channel' => 'partner',
            'changed_by' => session('user')['id'] ?? null,
            'note' => 'Shipped by partner via ' . $courier . ($awb ? ' (AWB: ' . $awb . ')' : ''),
        ]);
        // Shipped notifications
        try {
            $order = $db->table('orders')->where('id', $id)->get()->getRowArray();
            $shipment = $db->table('shipments')->where('id', $shipmentId)->get()->getRowArray();
            $trackingUrl = $shipment['tracking_url'] ?? ($awb ? "https://www.google.com/search?q=" . urlencode($courier . ' ' . $awb) : '');
            $payload = ['order_number' => $order['order_number'], 'courier' => $courier, 'awb' => $awb, 'tracking_url' => $trackingUrl];
            $notif = new NotificationService();
            if ($order['email']) $notif->send('email',    $order['email'], 'order.shipped', $payload, $order['user_id'], 'order', (int) $id);
            if ($order['phone']) $notif->send('whatsapp', $order['phone'], 'order.shipped', $payload, $order['user_id'], 'order', (int) $id);
        } catch (\Throwable $e) { /* swallow */ }

        // Try Shiprocket if configured (best-effort)
        try {
            $sr = new ShiprocketService();
            $sr->createOrder((int) $id);
        } catch (\Throwable $e) { /* not configured is fine */ }

        return redirect()->to('/partner/orders/' . $id)->with('success', 'Marked as shipped.');
    }

    public function updateAwb($id)
    {
        $db = Database::connect();
        $awb = $this->request->getPost('awb');
        $shipment = $db->table('shipments')->where('order_id', $id)->where('partner_id', $this->partner['id'])->orderBy('id','DESC')->limit(1)->get()->getRowArray();
        if ($shipment) {
            $db->table('shipments')->where('id', $shipment['id'])->update(['awb' => $awb]);
        }
        return redirect()->to('/partner/orders/' . $id)->with('success', 'AWB updated.');
    }
}
