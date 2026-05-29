<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\DigitalDeliveryService;
use App\Libraries\IntentService;
use Config\Database;

class AccountController extends BaseApiController
{
    public function orders()
    {
        $u = $this->requireAuth();
        $rows = Database::connect()->table('orders')
            ->select('id, order_number, status, grand_total, amount_paid, amount_due, payment_method, payment_mode, balance_due_payable_at, created_at, paid_at, shipped_at, delivered_at, warehouse_id, shipping_eta_days')
            ->where('user_id', $u['id'])
            ->orderBy('created_at', 'DESC')
            ->limit(100)->get()->getResultArray();
        return $this->ok(['orders' => $rows]);
    }

    public function orderDetail($id)
    {
        $u = $this->requireAuth();
        $db = Database::connect();
        $order = $db->table('orders')->where('id', (int) $id)->where('user_id', $u['id'])->get()->getRowArray();
        if (! $order) return $this->fail('Order not found', 404);
        $items = $db->table('order_items')->where('order_id', $id)->get()->getResultArray();
        $shipments = $db->table('shipments')->where('order_id', $id)->orderBy('id','DESC')->get()->getResultArray();
        return $this->ok([
            'order'     => $order,
            'items'     => $items,
            'shipments' => $shipments,
        ]);
    }

    public function downloads()
    {
        $u = $this->requireAuth();
        $rows = (new DigitalDeliveryService())->listForUser((int) $u['id']);
        // Augment with full download URL
        foreach ($rows as &$r) {
            $r['download_url'] = DigitalDeliveryService::buildUrl($r['token']);
        }
        return $this->ok(['downloads' => $rows]);
    }

    public function intentCapture()
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $res = (new IntentService())->capture(is_array($input) ? $input : []);
        return $this->response->setJSON($res);
    }

    public function intentVerify()
    {
        $iid = (int) ($this->request->getJsonVar('intent_id') ?: $this->request->getPost('intent_id'));
        $otp = (string) ($this->request->getJsonVar('otp') ?: $this->request->getPost('otp'));
        return $this->response->setJSON((new IntentService())->verifyOtp($iid, $otp));
    }
}
