<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\Cart\CartService;
use App\Libraries\IntentService;
use App\Libraries\Notifications\NotificationService;
use App\Libraries\PartnerFulfillmentService;
use App\Libraries\ReferralService;
use App\Libraries\Tracking\TrackingService;
use Config\Database;

/**
 * Single-item express enrolment for class / event / service / membership types.
 * BYPASSES the cart entirely — one decision, one transaction, one confirmation.
 *
 * Routes:
 *   GET  /enrol/{variantId}      — show the express form (variant + price + contact + payment)
 *   POST /enrol/{variantId}/pay  — create order + redirect to gateway / direct confirmation
 */
class EnrolController extends BaseStoreController
{
    public function show(int $variantId)
    {
        $row = $this->loadVariant($variantId);
        if (! $row) return redirect()->to('/shop');

        // Resolve type-specific metadata for the form pitch
        $meta = $this->loadTypeMeta($row);

        $user = session('user');
        $defaultAddress = null;
        if ($user) {
            $defaultAddress = Database::connect()->table('addresses')->where('user_id', $user['id'])->where('is_default', 1)->get()->getRowArray();
        }
        $prefill = [
            'name'  => $user['name']  ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
        ];

        // Resolve eligibility — only enrol-relevant methods (no COD, no partial_cod)
        $price = (int) $row['price'];
        $methods = $this->paymentMethodsFor($row['p_type'], $price);

        return $this->view('App\Modules\Storefront\Views\enrol', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'Enrol · ' . esc($row['p_name']) . ' — Khoobie',
                'description' => 'Complete your enrolment in seconds.',
            ]),
            'item'    => $row,
            'meta'    => $meta,
            'prefill' => $prefill,
            'methods' => $methods,
        ]);
    }

    public function pay(int $variantId)
    {
        $row = $this->loadVariant($variantId);
        if (! $row) return redirect()->to('/shop');

        $rules = [
            'name'           => 'required|min_length[2]',
            'email'          => 'required|valid_email',
            'phone'          => 'required|min_length[10]',
            'payment_method' => 'required|in_list[razorpay,phonepe,partial_venue,free_trial]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $price = (int) $row['price'];
        $paymentMethod = (string) $this->request->getPost('payment_method');

        // Type-aware payment mode
        $paymentMode = match ($paymentMethod) {
            'partial_venue' => 'partial_venue',
            'free_trial'    => 'free_trial',
            default         => 'prepaid',
        };

        // Compute amount due breakdown
        $advance  = 0;
        $balance  = 0;
        $balanceAt = null;
        if ($paymentMethod === 'partial_venue') {
            $advance = max(10000, (int) ceil($price * 0.20));
            $balance = $price - $advance;
            $balanceAt = in_array($row['p_type'], ['meetup'], true) ? 'venue' : ($row['p_type'] === 'tuition' ? 'class' : 'venue');
        }
        if ($paymentMethod === 'free_trial') {
            $advance = 0;
            $balance = $price;  // converts after trial
            $balanceAt = 'after_trial';
        }

        $grand = $price;
        $amountDue = match ($paymentMethod) {
            'partial_venue' => $balance,
            'free_trial'    => 0,
            default         => 0,
        };

        $orderNumber = 'KKE' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $db = Database::connect();
        $user = session('user');
        $contact = [
            'name'  => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
        ];

        $db->transStart();
        $db->table('orders')->insert([
            'order_number'           => $orderNumber,
            'user_id'                => $user['id'] ?? null,
            'lead_id'                => session('lead_id'),
            'status'                 => $paymentMethod === 'free_trial' ? 'trialing' : 'pending_payment',
            'confirmation_status'    => 'not_required',
            'email'                  => $contact['email'],
            'phone'                  => $contact['phone'],
            'name'                   => $contact['name'],
            'shipping_address'       => json_encode($contact + ['note' => 'enrolment — no shipping']),
            'billing_address'        => json_encode($contact),
            'shipping_method'        => 'no_shipping',
            'subtotal'               => $price,
            'grand_total'            => $grand,
            'amount_paid'            => 0,
            'amount_due'             => $amountDue,
            'balance_due_payable_at' => $balanceAt,
            'payment_method'         => $paymentMethod,
            'payment_mode'           => $paymentMode,
            'source'                 => 'express_enrol',
            'placed_at'              => date('Y-m-d H:i:s'),
            'attribution'            => json_encode(session('attribution') ?: []),
        ]);
        $orderId = (int) $db->insertID();

        $db->table('order_items')->insert([
            'order_id'         => $orderId,
            'product_id'       => $row['pid'],
            'variant_id'       => $variantId,
            'product_snapshot' => json_encode([
                'name'         => $row['p_name'],
                'sku'          => $variantId,
                'variant'      => $row['name'],
                'image'        => $row['hero_image'],
                'product_slug' => $row['p_slug'],
                'type'         => $row['p_type'],
            ]),
            'qty'              => 1,
            'unit_price'       => $price,
            'line_discount'    => 0,
            'line_subtotal'    => $price,
            'line_total'       => $price,
            'is_digital'       => 0,
            'fulfillment_status' => 'pending',
        ]);
        $db->table('order_status_history')->insert([
            'order_id' => $orderId, 'to_status' => $paymentMethod === 'free_trial' ? 'trialing' : 'pending_payment',
            'channel'  => 'web', 'note' => 'Express enrolment placed',
        ]);

        // Payment records
        if ($paymentMethod === 'partial_venue') {
            $db->table('payments')->insert(['order_id' => $orderId, 'gateway' => 'razorpay', 'amount' => $advance, 'is_advance' => 1, 'status' => 'initiated']);
            $db->table('payments')->insert(['order_id' => $orderId, 'gateway' => 'at_venue', 'amount' => $balance, 'status' => 'pending']);
        } elseif ($paymentMethod === 'free_trial') {
            // No payment row needed for free trial — it's tracked at the order level
        }

        $db->transComplete();

        // Notify partners + tracking + downloads (for course/membership digital access)
        try { (new PartnerFulfillmentService())->notifyPartnersOnPaid($orderId); } catch (\Throwable $e) {}
        try {
            (new TrackingService())->captureEvent([
                'event_name' => $paymentMethod === 'free_trial' ? 'TrialStarted' : 'InitiateCheckout',
                'value'      => $grand,
                'currency'   => 'INR',
                'email'      => $contact['email'],
                'phone'      => $contact['phone'],
                'url'        => current_url(),
                'source'     => 'server',
                'custom_data'=> ['order_id' => $orderNumber, 'product_type' => $row['p_type']],
            ]);
        } catch (\Throwable $e) {}

        // Send class-specific confirmation notifications
        try {
            $notif = new NotificationService();
            $payload = [
                'order_number' => $orderNumber,
                'name'         => $contact['name'],
                'product_name' => $row['p_name'],
                'product_type' => $row['p_type'],
                'amount'       => $grand,
                'amount_due'   => $amountDue,
                'amount_paid'  => 0,
                'payment_method'=> $paymentMethod,
            ];
            if ($contact['email']) $notif->send('email',    $contact['email'], 'enrol.confirmation',  $payload, $user['id'] ?? null, 'order', $orderId);
            if ($contact['phone']) $notif->send('whatsapp', $contact['phone'], 'enrol.confirmation',  $payload, $user['id'] ?? null, 'order', $orderId);
        } catch (\Throwable $e) {}

        // Free trial → straight to confirmation, no gateway
        if ($paymentMethod === 'free_trial') {
            return redirect()->to('/enrol/confirmed/' . $orderNumber);
        }

        // Otherwise → existing gateway redirect (Razorpay) — reuse pay_razorpay view via CheckoutController helpers
        $amountToCharge = $paymentMethod === 'partial_venue' ? $advance : $grand;
        if (in_array($paymentMethod, ['razorpay','partial_venue'], true)) {
            $rzp = (new \App\Libraries\Payments\RazorpayService())->createOrder($orderId, $amountToCharge, $orderNumber);
            if (! $rzp['ok']) {
                // Gateway not configured — order is recorded; show confirmation with "we'll contact you" copy
                return redirect()->to('/enrol/confirmed/' . $orderNumber)
                    ->with('error', 'Razorpay not configured — order saved, we will contact you for payment.');
            }
            return $this->view('App\Modules\Storefront\Views\pay_razorpay', [
                'page'   => array_merge($this->data['page'], ['title' => 'Complete payment']),
                'order'  => ['order_number' => $orderNumber, 'id' => $orderId] + $contact,
                'rzp'    => $rzp,
                'amount' => $amountToCharge,
            ]);
        }
        if ($paymentMethod === 'phonepe') {
            $merchantTxn = $orderNumber . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
            $pp = (new \App\Libraries\Payments\PhonePeService())->initiate(
                $orderId, $amountToCharge, $merchantTxn,
                base_url('api/payment/phonepe/callback'), base_url('api/payment/phonepe/callback'), $contact['phone']
            );
            if (! $pp['ok']) return redirect()->to('/enrol/confirmed/' . $orderNumber);
            return redirect()->to($pp['redirect_url']);
        }

        return redirect()->to('/enrol/confirmed/' . $orderNumber);
    }

    public function confirmed(string $orderNumber)
    {
        $order = Database::connect()->table('orders')->where('order_number', $orderNumber)->get()->getRowArray();
        if (! $order) return redirect()->to('/');
        $item = Database::connect()->table('order_items')->where('order_id', $order['id'])->get()->getRowArray();
        return $this->view('App\Modules\Storefront\Views\enrol_confirmed', [
            'page'  => array_merge($this->data['page'], ['title' => 'You\'re in! — Khoobie']),
            'order' => $order,
            'item'  => $item,
        ]);
    }

    // ====================================================================
    private function loadVariant(int $variantId): ?array
    {
        $row = Database::connect()->table('product_variants v')
            ->join('products p', 'p.id = v.product_id')
            ->select('v.*, p.id AS pid, p.slug AS p_slug, p.name AS p_name, p.short_desc AS p_short_desc, p.type AS p_type, p.hero_image')
            ->where('v.id', $variantId)->where('v.is_active', 1)->where('p.status', 'active')
            ->get()->getRowArray();
        return $row ?: null;
    }

    /** Per-type meta for the form: instructor / city / schedule / what's-included. */
    private function loadTypeMeta(array $row): array
    {
        $db = Database::connect();
        $pid = (int) $row['pid'];
        $type = $row['p_type'];
        $meta = ['type_label' => ucfirst($type), 'cta_primary' => 'Enrol Now'];

        switch ($type) {
            case 'tuition':
                $t = $db->table('tuitions')->where('product_id', $pid)->get()->getRowArray();
                if ($t) {
                    $meta = array_merge($meta, [
                        'type_label'  => 'Live online class',
                        'cta_primary' => 'Enrol monthly',
                        'instructor'  => $t['instructor_name'],
                        'schedule'    => implode(' / ', json_decode($t['days_of_week'] ?? '[]', true) ?: []) . ' · ' . substr($t['start_time'], 0, 5) . '–' . substr($t['end_time'], 0, 5),
                        'modality'    => $t['modality'],
                        'supports_trial'    => (bool) $t['trial_available'],
                        'billing_cycle'     => $t['billing_cycle'],
                    ]);
                }
                break;
            case 'course':
                $c = $db->table('courses')->where('product_id', $pid)->get()->getRowArray();
                if ($c) {
                    $meta = array_merge($meta, [
                        'type_label'  => 'Self-paced video course',
                        'cta_primary' => 'Enrol — start watching',
                        'instructor'  => $c['instructor_name'],
                        'lessons'     => (int) $c['lessons_count'],
                        'hours'       => round(((int) $c['total_minutes']) / 60, 1),
                        'certificate' => (bool) $c['certificate_available'],
                    ]);
                }
                break;
            case 'meetup':
                $m = $db->table('meetups')->where('product_id', $pid)->get()->getRowArray();
                if ($m) {
                    $meta = array_merge($meta, [
                        'type_label'      => 'In-person meetup',
                        'cta_primary'     => $m['is_free'] ? 'RSVP free' : 'Reserve seat',
                        'city'            => $m['city'],
                        'locality'        => $m['locality'] ?? '',
                        'area'            => $m['area']     ?? '',
                        'starts_at'       => $m['starts_at'],
                        'capacity_left'   => max(0, (int) $m['capacity'] - (int) $m['rsvp_count']),
                        'is_free'         => (bool) $m['is_free'],
                        'supports_venue_pay' => true,
                    ]);
                }
                break;
            case 'service':
                $s = $db->table('services')->where('product_id', $pid)->get()->getRowArray();
                if ($s) {
                    $meta = array_merge($meta, [
                        'type_label'  => '1-on-1 booking',
                        'cta_primary' => 'Book a slot',
                        'provider'    => $s['provider_name'],
                        'duration'    => (int) $s['duration_minutes'],
                        'modality'    => $s['modality'],
                    ]);
                }
                break;
            case 'membership':
                $mem = $db->table('memberships')->where('product_id', $pid)->get()->getRowArray();
                if ($mem) {
                    $meta = array_merge($meta, [
                        'type_label'   => 'Membership',
                        'cta_primary'  => 'Join — start trial',
                        'tier'         => $mem['tier_name'],
                        'monthly'      => (int) $mem['monthly_price'],
                        'annual'       => (int) $mem['annual_price'],
                        'supports_trial' => true,
                    ]);
                }
                break;
        }
        return $meta;
    }

    private function paymentMethodsFor(string $type, int $price): array
    {
        $methods = [
            'razorpay' => ['available' => true, 'icon' => '💳', 'label' => 'UPI / Card / Netbanking', 'sub' => 'Pay ₹' . number_format(round($price/100)) . ' now via Razorpay'],
            'phonepe'  => ['available' => true, 'icon' => '📱', 'label' => 'PhonePe', 'sub' => 'Pay via PhonePe wallet/UPI'],
        ];

        // Part-pay-at-venue for meetup + service
        if (in_array($type, ['meetup','service'], true)) {
            $advance = max(10000, (int) ceil($price * 0.20));
            $balance = $price - $advance;
            $methods['partial_venue'] = [
                'available' => true,
                'icon'      => '🎟️',
                'label'     => $type === 'meetup' ? 'Book seat now, balance at venue' : 'Book slot, balance at session',
                'sub'       => 'Pay ₹' . number_format(round($advance/100)) . ' now · ₹' . number_format(round($balance/100)) . ' at the ' . ($type === 'meetup' ? 'venue' : 'session'),
                'advance'   => $advance,
                'balance'   => $balance,
            ];
        }

        // Free trial for tuition + membership
        if (in_array($type, ['tuition','membership'], true)) {
            $methods['free_trial'] = [
                'available' => true,
                'icon'      => '🎓',
                'label'     => $type === 'tuition' ? 'Start with FREE trial class' : 'Start 7-day FREE trial',
                'sub'       => 'Pay nothing now · cancel anytime before trial ends',
            ];
        }

        return $methods;
    }
}
