<?php

namespace App\Modules\Customer\Controllers;

use App\Modules\Storefront\Controllers\BaseStoreController;
use Config\Database;

class AccountController extends BaseStoreController
{
    public function index()
    {
        $user = session('user');
        $db   = Database::connect();

        $loyalty = $db->table('loyalty_accounts')->where('user_id', $user['id'])->get()->getRowArray() ?? ['points_balance' => 0, 'tier' => 'bronze'];
        $recentOrders = $db->table('orders')
            ->where('user_id', $user['id'])
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        // Verification status for the dashboard banner
        $verify = $db->table('users')->where('id', $user['id'])
            ->select('email_verified_at, phone_verified_at, email, phone')
            ->get()->getRowArray() ?: [];

        // Personal coupons earned via verification (or any reward)
        $personalCoupons = $db->table('coupons')
            ->where('restricted_to_user_id', $user['id'])
            ->where('is_active', 1)
            ->where('(max_uses IS NULL OR used_count < max_uses)', null, false)
            ->where('(ends_at IS NULL OR ends_at >= NOW())', null, false)
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        return $this->view('App\Modules\Customer\Views\dashboard', [
            'page'           => array_merge($this->data['page'], ['title' => 'My Account — Khoobie']),
            'loyalty'        => $loyalty,
            'recentOrders'   => $recentOrders,
            'verify'         => $verify,
            'personalCoupons'=> $personalCoupons,
        ]);
    }

    public function orders()
    {
        $user = session('user');
        $orders = Database::connect()->table('orders')
            ->where('user_id', $user['id'])
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
        return $this->view('App\Modules\Customer\Views\orders', [
            'page' => array_merge($this->data['page'], ['title' => 'My Orders']),
            'orders' => $orders,
        ]);
    }

    public function orderDetail($id)
    {
        $user = session('user');
        $db = Database::connect();
        $order = $db->table('orders')->where('id', $id)->where('user_id', $user['id'])->get()->getRowArray();
        if (! $order) return redirect()->to('/account/orders');
        $items = $db->table('order_items')->where('order_id', $id)->get()->getResultArray();
        return $this->view('App\Modules\Customer\Views\order_detail', [
            'page' => array_merge($this->data['page'], ['title' => 'Order #' . $order['order_number']]),
            'order' => $order,
            'items' => $items,
        ]);
    }

    public function downloads()
    {
        $user = session('user');
        $rows = (new \App\Libraries\DigitalDeliveryService())->listForUser((int) $user['id']);
        return $this->view('App\Modules\Customer\Views\downloads', [
            'page' => array_merge($this->data['page'], ['title' => 'My Downloads']),
            'rows' => $rows,
        ]);
    }

    public function buyAgain()
    {
        $user = session('user');
        $db = Database::connect();
        // Distinct products from past orders, joined with current product details
        $rows = $db->query("
            SELECT DISTINCT p.id, p.sku, p.slug, p.name, p.hero_image, p.short_desc, p.age_min_years, p.age_max_years,
                   p.rating_avg, p.rating_count, p.sales_count, p.is_featured, p.published_at,
                   v.id AS variant_id, v.price, v.compare_at_price,
                   MAX(o.created_at) AS last_bought
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            LEFT JOIN product_variants v ON v.product_id = p.id AND v.is_default = 1
            WHERE o.user_id = ? AND o.status NOT IN ('cancelled','failed','pending_payment')
              AND p.status = 'active'
            GROUP BY p.id
            ORDER BY last_bought DESC
            LIMIT 24
        ", [(int) $user['id']])->getResultArray();
        return $this->view('App\Modules\Customer\Views\buy_again', [
            'page' => array_merge($this->data['page'], ['title' => 'Buy Again']),
            'rows' => $rows,
        ]);
    }

    public function trackOrder($id)
    {
        $user = session('user');
        $db = Database::connect();
        $order = $db->table('orders')->where('id', (int) $id)->where('user_id', $user['id'])->get()->getRowArray();
        if (! $order) return redirect()->to('/account/orders');
        $items     = $db->table('order_items')->where('order_id', $id)->get()->getResultArray();
        $history   = $db->table('order_status_history')->where('order_id', $id)->orderBy('id','ASC')->get()->getResultArray();
        $shipments = $db->table('shipments')->where('order_id', $id)->orderBy('id','DESC')->get()->getResultArray();
        $payments  = $db->table('payments')->where('order_id', $id)->orderBy('id','DESC')->get()->getResultArray();
        return $this->view('App\Modules\Customer\Views\track_order', [
            'page'      => array_merge($this->data['page'], ['title' => 'Track #' . $order['order_number']]),
            'order'     => $order,
            'items'     => $items,
            'history'   => $history,
            'shipments' => $shipments,
            'payments'  => $payments,
        ]);
    }

    public function requestReturn($orderId)
    {
        $user = session('user');
        $db = Database::connect();
        $order = $db->table('orders')->where('id', (int) $orderId)->where('user_id', $user['id'])->get()->getRowArray();
        if (! $order) return redirect()->to('/account/orders');
        if (! in_array($order['status'] ?? '', ['delivered','paid'], true)) {
            return redirect()->back()->with('error', 'Returns can only be requested on delivered/paid orders.');
        }
        $reason = trim((string) $this->request->getPost('reason'));
        $desc   = trim((string) $this->request->getPost('description'));
        if (! $reason) return redirect()->back()->with('error', 'Please pick a reason.');

        $rn = 'KR' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $db->table('returns')->insert([
            'order_id'       => $order['id'],
            'return_number'  => $rn,
            'type'           => 'return',
            'reason'         => $reason,
            'description'    => $desc,
            'status'         => 'requested',
            'refund_amount'  => 0,
            'pickup_address' => $order['shipping_address'],
            'items'          => json_encode($db->table('order_items')->where('order_id', $order['id'])->get()->getResultArray()),
        ]);
        return redirect()->to('/account/orders/' . $order['id'])
            ->with('success', '✓ Return request #' . $rn . ' submitted. We\'ll confirm pickup details within 24h.');
    }

    public function addresses()
    {
        $user = session('user');
        $rows = Database::connect()->table('addresses')
            ->where('user_id', $user['id'])
            ->orderBy('is_default', 'DESC')->orderBy('id', 'DESC')
            ->get()->getResultArray();
        return $this->view('App\Modules\Customer\Views\addresses', [
            'page' => array_merge($this->data['page'], ['title' => 'Saved Addresses']),
            'rows' => $rows,
        ]);
    }

    public function addressSave()
    {
        $user = session('user');
        $rules = [
            'label'   => 'permit_empty|max_length[50]',
            'name'    => 'required|min_length[2]|max_length[150]',
            'phone'   => 'required|min_length[10]|max_length[15]',
            'line1'   => 'required|min_length[3]',
            'city'    => 'required',
            'state'   => 'required',
            'pincode' => 'required|min_length[6]|max_length[10]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $db = Database::connect();
        $id = (int) $this->request->getPost('id');

        $data = [
            'user_id' => $user['id'],
            'label'   => $this->request->getPost('label') ?: 'Home',
            'name'    => $this->request->getPost('name'),
            'phone'   => $this->request->getPost('phone'),
            'line1'   => $this->request->getPost('line1'),
            'line2'   => $this->request->getPost('line2'),
            'landmark'=> $this->request->getPost('landmark'),
            'city'    => $this->request->getPost('city'),
            'state'   => $this->request->getPost('state'),
            'pincode' => $this->request->getPost('pincode'),
            'country' => 'IN',
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
        ];
        // Only one default
        if ($data['is_default']) {
            $db->table('addresses')->where('user_id', $user['id'])->update(['is_default' => 0]);
        }
        if ($id) {
            $db->table('addresses')->where('id', $id)->where('user_id', $user['id'])->update($data);
        } else {
            $db->table('addresses')->insert($data);
        }
        return redirect()->to('/account/addresses')->with('success', '✓ Address saved.');
    }

    public function addressDelete($id)
    {
        $user = session('user');
        Database::connect()->table('addresses')->where('id', (int) $id)->where('user_id', $user['id'])->delete();
        return redirect()->to('/account/addresses')->with('success', 'Address removed.');
    }

    public function profile()
    {
        $user = session('user');
        $row = Database::connect()->table('users')->where('id', $user['id'])->get()->getRowArray();
        return $this->view('App\Modules\Customer\Views\profile', [
            'page' => array_merge($this->data['page'], ['title' => 'Profile']),
            'user' => $row,
        ]);
    }

    public function profileUpdate()
    {
        $user = session('user');
        $rules = [
            'name'  => 'required|min_length[2]|max_length[150]',
            'email' => 'required|valid_email',
            'phone' => 'required|min_length[10]|max_length[15]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $db = Database::connect();
        $row = $db->table('users')->where('id', $user['id'])->get()->getRow();
        if (! $row) return redirect()->to('/login');

        $email = trim((string) $this->request->getPost('email'));
        $phone = trim((string) $this->request->getPost('phone'));

        $updates = [
            'name'  => $this->request->getPost('name'),
            'email' => $email,
            'phone' => $phone,
        ];
        // If email/phone changed, force re-verification
        if ($email !== $row->email) $updates['email_verified_at'] = null;
        if ($phone !== $row->phone) $updates['phone_verified_at'] = null;

        $db->table('users')->where('id', $row->id)->update($updates);

        // Refresh session
        session()->set('user', array_merge((array) $user, ['name' => $updates['name'], 'email' => $email, 'phone' => $phone]));

        return redirect()->to('/account/profile')->with('success', '✓ Profile saved.');
    }
    public function wallet()     {
        $user = session('user');
        $db = Database::connect();
        $loyalty = $db->table('loyalty_accounts')->where('user_id', $user['id'])->get()->getRowArray() ?? ['points_balance'=>0,'lifetime_points'=>0,'tier'=>'bronze'];
        $txns = $db->table('loyalty_transactions')->where('user_id', $user['id'])->orderBy('id','DESC')->limit(30)->get()->getResultArray();
        return $this->view('App\Modules\Customer\Views\wallet', [
            'page' => array_merge($this->data['page'], ['title' => 'Loyalty Wallet']),
            'loyalty' => $loyalty, 'txns' => $txns,
        ]);
    }
    public function wishlist()      { return redirect()->to('/shortlist'); }
    public function subscriptions() {
        $user = session('user');
        $db = Database::connect();
        $rows = $db->table('subscriptions s')
            ->select('s.id, s.status, s.starts_at, s.ends_at, s.next_billing_at, s.qty,
                      sp.name AS plan_name, sp.amount AS plan_amount, sp.billing_cycle, sp.product_id,
                      p.name AS product_name, p.slug AS product_slug, p.hero_image')
            ->join('subscription_plans sp', 'sp.id = s.plan_id', 'left')
            ->join('products p', 'p.id = sp.product_id', 'left')
            ->where('s.user_id', $user['id'])
            ->orderBy('s.id', 'DESC')->get()->getResultArray();
        return $this->view('App\Modules\Customer\Views\subscriptions', [
            'page' => array_merge($this->data['page'], ['title' => 'My Subscriptions']),
            'rows' => $rows,
        ]);
    }
    public function subscriptionCancel($id)
    {
        $user = session('user');
        Database::connect()->table('subscriptions')->where('id', (int) $id)->where('user_id', $user['id'])->update([
            'status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/account/subscriptions')->with('success', 'Subscription cancelled.');
    }
    public function subscriptionPause($id)
    {
        $user = session('user');
        Database::connect()->table('subscriptions')->where('id', (int) $id)->where('user_id', $user['id'])->update([
            'status' => 'paused',
        ]);
        return redirect()->to('/account/subscriptions')->with('success', 'Subscription paused.');
    }
    public function subscriptionResume($id)
    {
        $user = session('user');
        Database::connect()->table('subscriptions')->where('id', (int) $id)->where('user_id', $user['id'])->update([
            'status' => 'active',
        ]);
        return redirect()->to('/account/subscriptions')->with('success', 'Subscription resumed.');
    }
    public function referrals()     { return redirect()->to('/account/referrals'); }

    protected function stub(string $title)
    {
        return $this->view('App\Modules\Customer\Views\stub', [
            'page' => array_merge($this->data['page'], ['title' => $title]),
            'heading' => $title,
        ]);
    }
}
