<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\Cart\CartService;
use App\Libraries\Notifications\NotificationService;
use App\Libraries\Payments\PhonePeService;
use App\Libraries\Payments\RazorpayService;
use App\Libraries\Tracking\TrackingService;
use Config\Database;

class CheckoutController extends BaseStoreController
{
    public function index()
    {
        $cs = new CartService();
        $cart = $cs->getCartWithItems();
        if (empty($cart['items'])) {
            return redirect()->to('/shop')->with('error', 'Your cart is empty.');
        }

        $user = session('user');
        $lead = session('lead_prefill') ?? [];
        $defaultAddress = null;
        if ($user) {
            $defaultAddress = Database::connect()->table('addresses')->where('user_id', $user['id'])->where('is_default', 1)->get()->getRowArray();
        }

        // Type-aware payment eligibility — single source of truth for which methods to show
        $eligibility = $cs->getPaymentEligibility();

        // Address only required if there's something to ship (anything physical)
        $needsShipping = $eligibility['flags']['has_physical'] ?? false;

        return $this->view('App\Modules\Storefront\Views\checkout', [
            'page' => array_merge($this->data['page'], ['title' => 'Checkout — Krafty Khoobie']),
            'cart' => $cart['cart'],
            'items' => $cart['items'],
            'promotions' => $cart['promotions'],
            'prefill' => array_merge([
                'name'  => $user['name']  ?? ($lead['name']  ?? ''),
                'email' => $user['email'] ?? ($lead['email'] ?? ''),
                'phone' => $user['phone'] ?? ($lead['phone'] ?? ''),
            ], $defaultAddress ? [
                'line1' => $defaultAddress['line1'],
                'line2' => $defaultAddress['line2'],
                'city'  => $defaultAddress['city'],
                'state' => $defaultAddress['state'],
                'pincode' => $defaultAddress['pincode'],
            ] : []),
            'eligibility'   => $eligibility,
            'needsShipping' => $needsShipping,
        ]);
    }

    public function place()
    {
        $cs = new CartService();
        $cart = $cs->getCartWithItems();
        if (empty($cart['items'])) return redirect()->to('/shop');

        $eligibility = $cs->getPaymentEligibility();
        $needsShipping = $eligibility['flags']['has_physical'] ?? false;

        $rules = [
            'name'           => 'required|min_length[2]|max_length[150]',
            'email'          => 'required|valid_email',
            'phone'          => 'required|min_length[10]',
            'payment_method' => 'required|in_list[razorpay,phonepe,cod,partial_cod,partial_venue]',
        ];
        // Shipping address only required if cart has physical goods
        if ($needsShipping) {
            $rules += [
                'line1'   => 'required|min_length[3]',
                'city'    => 'required',
                'state'   => 'required',
                'pincode' => 'required|min_length[6]|max_length[10]',
            ];
        }
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Enforce server-side that the method picked is actually eligible
        $paymentMethod = $this->request->getPost('payment_method');
        if (empty($eligibility['methods'][$paymentMethod]['available'])) {
            return redirect()->back()->withInput()->with('errors', [
                'payment_method' => 'That payment method isn\'t available for this cart.',
            ]);
        }

        $req = $this->request;
        $db = Database::connect();
        $user = session('user');

        $shipping = [
            'name'    => $req->getPost('name'),
            'email'   => $req->getPost('email'),
            'phone'   => $req->getPost('phone'),
            'line1'   => $req->getPost('line1') ?: '—',
            'line2'   => $req->getPost('line2'),
            'city'    => $req->getPost('city')  ?: '—',
            'state'   => $req->getPost('state') ?: '—',
            'pincode' => $req->getPost('pincode') ?: '000000',
            'country' => 'IN',
        ];

        $paymentMode = match($paymentMethod) {
            'cod'            => 'cod',
            'partial_cod'    => 'partial_cod',
            'partial_venue'  => 'partial_venue',
            default          => 'prepaid',
        };

        $codFee = 0;
        $advanceAmount = 0;
        $balancePayableAt = null;

        if (in_array($paymentMethod, ['cod', 'partial_cod'], true)) {
            $codFee = (int) $cs->setting('shipping', 'cod_fee', 4900);
            if ($paymentMethod === 'partial_cod') {
                $advanceAmount = (int) ($eligibility['methods']['partial_cod']['advance'] ?? 0);
                $balancePayableAt = 'delivery';
            }
        } elseif ($paymentMethod === 'partial_venue') {
            $advanceAmount    = (int) ($eligibility['methods']['partial_venue']['advance'] ?? 0);
            $balancePayableAt = (string) ($eligibility['methods']['partial_venue']['balance_at'] ?? 'venue');
        }

        $grandTotal = (int) $cart['cart']['grand_total'] + $codFee;
        $amountDue = match($paymentMethod) {
            'cod'           => $grandTotal,
            'partial_cod'   => $grandTotal - $advanceAmount,
            'partial_venue' => $grandTotal - $advanceAmount,
            default         => 0,
        };

        $orderNumber = 'KK' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $db->transStart();
        $db->table('orders')->insert([
            'order_number'    => $orderNumber,
            'user_id'         => $user['id'] ?? null,
            'lead_id'         => session('lead_id'),
            'status'          => $paymentMethod === 'cod' ? 'pending_confirmation' : 'pending_payment',
            'confirmation_status' => $paymentMethod === 'cod' ? 'pending' : 'not_required',
            'email'           => $shipping['email'],
            'phone'           => $shipping['phone'],
            'name'            => $shipping['name'],
            'shipping_address'=> json_encode($shipping),
            'billing_address' => json_encode($shipping),
            'shipping_method' => $needsShipping ? 'standard' : 'no_shipping',
            'attribution'     => json_encode(session('attribution') ?: []),
            'warehouse_id'    => $needsShipping ? \App\Libraries\WarehouseRoutingService::routeForPincode($shipping['pincode'])['warehouse_id'] : null,
            'shipping_eta_days'=> $needsShipping ? \App\Libraries\WarehouseRoutingService::routeForPincode($shipping['pincode'])['estimated_days'] : null,
            'subtotal'        => $cart['cart']['subtotal'],
            'discount_total'  => $cart['cart']['discount_total'],
            'tax_total'       => $cart['cart']['tax_total'],
            'shipping_total'  => $needsShipping ? $cart['cart']['shipping_total'] : 0,
            'cod_fee'         => $codFee,
            'grand_total'     => $grandTotal,
            'amount_paid'     => 0,
            'amount_due'      => $amountDue,
            'balance_due_payable_at' => $balancePayableAt,
            'payment_method'  => $paymentMethod,
            'payment_mode'    => $paymentMode,
            'source'          => 'web',
            'placed_at'       => date('Y-m-d H:i:s'),
        ]);
        $orderId = (int) $db->insertID();

        foreach ($cart['items'] as $it) {
            $snap = [
                'name'    => $it['product_name'],
                'sku'     => $it['variant_id'],
                'variant' => $it['variant_name'],
                'image'   => $it['hero_image'],
                'product_slug' => $it['product_slug'],
            ];
            $db->table('order_items')->insert([
                'order_id'         => $orderId,
                'product_id'       => $it['product_id'],
                'variant_id'       => $it['variant_id'],
                'product_snapshot' => json_encode($snap),
                'qty'              => $it['qty'],
                'unit_price'       => $it['unit_price'],
                'line_discount'    => $it['line_discount'],
                'line_subtotal'    => $it['unit_price'] * $it['qty'],
                'line_total'       => $it['line_total'],
                'is_digital'       => $it['product_type'] === 'digital' ? 1 : 0,
                'fulfillment_status'=> 'pending',
            ]);
        }

        $db->table('order_status_history')->insert([
            'order_id'   => $orderId,
            'to_status'  => $paymentMethod === 'cod' ? 'pending_confirmation' : 'pending_payment',
            'channel'    => 'web',
            'note'       => 'Order placed by customer',
        ]);

        // Persist initial payment row(s) per payment method
        if ($paymentMethod === 'cod') {
            $db->table('payments')->insert([
                'order_id' => $orderId, 'gateway' => 'cod', 'amount' => $grandTotal, 'status' => 'pending',
            ]);
        } elseif ($paymentMethod === 'partial_cod') {
            // Advance via Razorpay
            $db->table('payments')->insert([
                'order_id' => $orderId, 'gateway' => 'razorpay', 'amount' => $advanceAmount, 'is_advance' => 1, 'status' => 'initiated',
            ]);
            // Balance row to be captured at delivery
            $db->table('payments')->insert([
                'order_id' => $orderId, 'gateway' => 'cod', 'amount' => $grandTotal - $advanceAmount, 'status' => 'pending',
            ]);
        } elseif ($paymentMethod === 'partial_venue') {
            // Advance via Razorpay
            $db->table('payments')->insert([
                'order_id' => $orderId, 'gateway' => 'razorpay', 'amount' => $advanceAmount, 'is_advance' => 1, 'status' => 'initiated',
            ]);
            // Balance — collected manually at the venue/class/center by the instructor
            $db->table('payments')->insert([
                'order_id' => $orderId, 'gateway' => 'at_venue', 'amount' => $grandTotal - $advanceAmount, 'status' => 'pending',
            ]);
        }

        $cs->clearForOrder((int) $cart['cart']['id']);
        $db->transComplete();

        // Server-side Purchase / InitiateCheckout
        try {
            (new TrackingService())->captureEvent([
                'event_name' => $paymentMethod === 'cod' ? 'Purchase' : 'InitiateCheckout',
                'value'      => $grandTotal,
                'currency'   => 'INR',
                'email'      => $shipping['email'],
                'phone'      => $shipping['phone'],
                'url'        => current_url(),
                'source'     => 'server',
                'custom_data'=> [
                    'order_id'    => $orderNumber,
                    'content_ids' => array_map(fn($i) => $i['variant_id'], $cart['items']),
                ],
            ]);
        } catch (\Throwable $e) { /* noop */ }

        // Dispatch order-placed notifications (email + WhatsApp + SMS)
        try {
            $notif = new NotificationService();
            $payload = ['order_number' => $orderNumber, 'name' => $shipping['name'], 'amount' => $grandTotal];
            if ($shipping['email']) $notif->send('email',    $shipping['email'], 'order.placed', $payload, $user['id'] ?? null, 'order', $orderId);
            if ($shipping['phone']) $notif->send('whatsapp', $shipping['phone'], 'order.placed', $payload, $user['id'] ?? null, 'order', $orderId);
            if ($shipping['phone']) $notif->send('sms',      $shipping['phone'], 'order.placed', $payload, $user['id'] ?? null, 'order', $orderId);
        } catch (\Throwable $e) { /* providers may be unconfigured locally */ }

        if ($paymentMethod === 'cod') {
            return redirect()->to('/checkout/thank-you/' . $orderNumber);
        }

        // If any items in the cart belong to a partner, fire partner notifications + stamp partner_id on order_items
        try { (new \App\Libraries\PartnerFulfillmentService())->notifyPartnersOnPaid($orderId); }
        catch (\Throwable $e) { log_message('error', 'Partner fulfillment notify failed: ' . $e->getMessage()); }

        // For COD-equivalent flows with digital items, the download URLs would also be issued on order confirmation.
        // For prepaid, we issue them as soon as the order is created so they're ready when payment lands.
        if ($eligibility['flags']['has_digital'] ?? false) {
            try {
                $links = (new \App\Libraries\DigitalDeliveryService())->issueForOrder($orderId);
                if (! empty($links)) {
                    // Email the download links — separate email so it stands out
                    $notif = new \App\Libraries\Notifications\NotificationService();
                    $body  = "Your downloads are ready:\n\n";
                    foreach ($links as $l) $body .= "- {$l['product_name']} → {$l['url']}\n";
                    $notif->send('email', $shipping['email'], 'order.downloads_ready',
                        ['order_number' => $orderNumber, 'name' => $shipping['name'], 'links' => $links, 'message' => $body],
                        $user['id'] ?? null, 'order', $orderId);
                }
            } catch (\Throwable $e) { log_message('error', 'Issue downloads failed: ' . $e->getMessage()); }
        }

        $amountToCharge = in_array($paymentMethod, ['partial_cod','partial_venue'], true) ? $advanceAmount : $grandTotal;
        if (in_array($paymentMethod, ['razorpay','partial_cod','partial_venue'], true)) {
            $rzp = (new RazorpayService())->createOrder($orderId, $amountToCharge, $orderNumber);
            if (! $rzp['ok']) {
                return redirect()->to('/checkout/thank-you/' . $orderNumber)
                    ->with('error', 'Razorpay not configured — order saved, complete payment after we contact you. (' . $rzp['error'] . ')');
            }
            return $this->view('App\Modules\Storefront\Views\pay_razorpay', [
                'page'   => array_merge($this->data['page'], ['title' => 'Complete payment']),
                'order'  => ['order_number' => $orderNumber, 'id' => $orderId, 'name' => $shipping['name'], 'email' => $shipping['email'], 'phone' => $shipping['phone']],
                'rzp'    => $rzp,
                'amount' => $amountToCharge,
            ]);
        }
        if ($paymentMethod === 'phonepe') {
            $merchantTxn = $orderNumber . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
            $pp = (new PhonePeService())->initiate(
                $orderId,
                $amountToCharge,
                $merchantTxn,
                base_url('api/payment/phonepe/callback'),
                base_url('api/payment/phonepe/callback'),
                $shipping['phone']
            );
            if (! $pp['ok']) {
                return redirect()->to('/checkout/thank-you/' . $orderNumber)
                    ->with('error', 'PhonePe not configured — order saved. (' . $pp['error'] . ')');
            }
            return redirect()->to($pp['redirect_url']);
        }
        return redirect()->to('/checkout/thank-you/' . $orderNumber);
    }

    public function thankYou(string $orderNumber)
    {
        $order = Database::connect()->table('orders')->where('order_number', $orderNumber)->get()->getRowArray();
        if (! $order) return redirect()->to('/');
        return $this->view('App\Modules\Storefront\Views\thank_you', [
            'page' => array_merge($this->data['page'], ['title' => 'Thank you for your order!']),
            'order' => $order,
        ]);
    }
}
