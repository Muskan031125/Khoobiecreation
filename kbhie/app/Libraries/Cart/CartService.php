<?php

namespace App\Libraries\Cart;

use Config\Database;
use Config\Services;

/**
 * Cart logic — works for guest (anon_id cookie) and logged-in users.
 * One active cart per identity; cart_items snapshot pricing.
 */
class CartService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getCurrentCart(bool $create = false): ?array
    {
        $user = session('user');
        $userId = $user['id'] ?? null;
        $anonId = $this->anonId();

        $b = $this->db->table('carts')->where('grand_total >=', 0); // any
        if ($userId) {
            $b->where('user_id', $userId);
        } else {
            $b->where('anon_id', $anonId);
        }
        $cart = $b->orderBy('updated_at', 'DESC')->limit(1)->get()->getRowArray();

        if (! $cart && $create) {
            $this->db->table('carts')->insert([
                'user_id' => $userId,
                'anon_id' => $anonId,
                'currency'=> 'INR',
            ]);
            $cart = $this->db->table('carts')->where('id', $this->db->insertID())->get()->getRowArray();
        }
        return $cart ?: null;
    }

    /**
     * Types that can be added to the shared cart.
     * Class / event / service / membership types are EXPRESS-ENROL only —
     * they route through /enrol/{variantId} for a single-item express checkout
     * because their refund policy, payment mode, scheduling, and confirmation
     * flow are item-specific and don't mix cleanly with each other or with kits.
     */
    public const CARTABLE_TYPES   = ['simple', 'variable', 'bundle', 'digital'];
    public const ENROL_ONLY_TYPES = ['course', 'tuition', 'meetup', 'service', 'membership', 'workshop', 'camp', 'webinar'];

    public function add(int $variantId, int $qty = 1): array
    {
        $variant = $this->db->table('product_variants v')
            ->join('products p', 'p.id = v.product_id')
            ->where('v.id', $variantId)
            ->where('v.is_active', 1)
            ->where('p.status', 'active')
            ->select('v.*, p.id AS pid, p.slug AS p_slug, p.name AS p_name, p.type AS p_type, p.hero_image')
            ->get()->getRowArray();
        if (! $variant) return ['ok' => false, 'error' => 'Product unavailable.'];

        // Hard guard: enrol-only types skip the cart and go straight to express enrol.
        if (in_array($variant['p_type'], self::ENROL_ONLY_TYPES, true)) {
            return [
                'ok'           => false,
                'error'        => 'Classes & events check out one at a time. Use Enrol Now to complete this booking.',
                'redirect_to'  => '/enrol/' . $variantId,
                'enrol_only'   => true,
                'product_slug' => $variant['p_slug'] ?? null,
            ];
        }

        // Hard guard: affiliate products are NOT sold by Khoobie — they redirect
        // to the partner marketplace (Amazon/Flipkart/Meesho) for checkout there.
        // Khoobie doesn't take payment, fulfil, or award points on these.
        if ($variant['p_type'] === 'affiliate') {
            return [
                'ok'           => false,
                'error'        => 'This product is sold on a partner marketplace — opening it now.',
                'redirect_to'  => '/go/' . ($variant['p_slug'] ?? ''),
                'affiliate'    => true,
                'product_slug' => $variant['p_slug'] ?? null,
            ];
        }

        $cart = $this->getCurrentCart(true);
        $existing = $this->db->table('cart_items')
            ->where('cart_id', $cart['id'])->where('variant_id', $variantId)->get()->getRowArray();

        if ($existing) {
            $newQty = (int) $existing['qty'] + $qty;
            $this->db->table('cart_items')->where('id', $existing['id'])->update([
                'qty'        => $newQty,
                'line_total' => $newQty * (int) $variant['price'],
            ]);
        } else {
            $newQty = $qty;
            $this->db->table('cart_items')->insert([
                'cart_id'    => $cart['id'],
                'product_id' => $variant['pid'],
                'variant_id' => $variantId,
                'qty'        => $qty,
                'unit_price' => $variant['price'],
                'line_total' => $qty * (int) $variant['price'],
            ]);
        }

        $this->recalculate($cart['id']);
        $updated = $this->db->table('carts')->where('id', $cart['id'])->get()->getRowArray();
        return [
            'ok'           => true,
            'cart_id'      => $cart['id'],
            'variant_id'   => $variantId,
            'variant_qty'  => (int) $newQty,        // total qty of this variant in cart (after add)
            'item_count'   => (int) $updated['item_count'],
            'grand_total'  => (int) $updated['grand_total'],
            'product_name' => $variant['p_name'],
            'product_image'=> $variant['hero_image'],
            'qty_added'    => $qty,
        ];
    }

    public function updateQty(int $itemId, int $qty): array
    {
        if ($qty <= 0) return $this->remove($itemId);
        $item = $this->db->table('cart_items')->where('id', $itemId)->get()->getRowArray();
        if (! $item) return ['ok' => false, 'error' => 'Item not found.'];
        $this->db->table('cart_items')->where('id', $itemId)->update([
            'qty' => $qty,
            'line_total' => $qty * (int) $item['unit_price'],
        ]);
        $this->recalculate((int) $item['cart_id']);
        return ['ok' => true];
    }

    public function remove(int $itemId): array
    {
        $item = $this->db->table('cart_items')->where('id', $itemId)->get()->getRowArray();
        if (! $item) return ['ok' => false, 'error' => 'Item not found.'];
        $this->db->table('cart_items')->where('id', $itemId)->delete();
        $this->recalculate((int) $item['cart_id']);
        return ['ok' => true];
    }

    /**
     * Set absolute qty by variant_id (used by product-card +/− stepper).
     * qty=0 → remove the line entirely.
     * Returns ['ok'=>true, 'qty'=>N, 'item_count'=>N, 'grand_total'=>N, 'product_name'=>str].
     */
    public function setQty(int $variantId, int $qty): array
    {
        $cart = $this->getCurrentCart(true);
        $variant = $this->db->table('product_variants v')
            ->join('products p', 'p.id = v.product_id')
            ->where('v.id', $variantId)
            ->select('v.*, p.name AS p_name, p.hero_image, p.id AS pid')
            ->get()->getRowArray();
        if (! $variant) return ['ok' => false, 'error' => 'Product unavailable.'];

        $existing = $this->db->table('cart_items')
            ->where('cart_id', $cart['id'])->where('variant_id', $variantId)
            ->get()->getRowArray();

        if ($qty <= 0) {
            if ($existing) $this->db->table('cart_items')->where('id', $existing['id'])->delete();
        } elseif ($existing) {
            $this->db->table('cart_items')->where('id', $existing['id'])->update([
                'qty'        => $qty,
                'line_total' => $qty * (int) $variant['price'],
            ]);
        } else {
            $this->db->table('cart_items')->insert([
                'cart_id'    => $cart['id'],
                'product_id' => $variant['pid'],
                'variant_id' => $variantId,
                'qty'        => $qty,
                'unit_price' => $variant['price'],
                'line_total' => $qty * (int) $variant['price'],
            ]);
        }

        $this->recalculate((int) $cart['id']);
        $updated = $this->db->table('carts')->where('id', $cart['id'])->get()->getRowArray();
        return [
            'ok'           => true,
            'variant_id'   => $variantId,
            'qty'          => max(0, $qty),
            'item_count'   => (int) $updated['item_count'],
            'grand_total'  => (int) $updated['grand_total'],
            'product_name' => $variant['p_name'],
            'product_image'=> $variant['hero_image'],
        ];
    }

    /** Returns [variant_id => qty] for the current cart — used to render in-cart state on product cards. */
    public function variantQtys(): array
    {
        $cart = $this->getCurrentCart();
        if (! $cart) return [];
        $rows = $this->db->table('cart_items')
            ->select('variant_id, qty')
            ->where('cart_id', $cart['id'])
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) $map[(int) $r['variant_id']] = (int) $r['qty'];
        return $map;
    }

    public function applyCoupon(string $code): array
    {
        $cart = $this->getCurrentCart();
        if (! $cart) return ['ok' => false, 'error' => 'No active cart.'];

        $coupon = $this->db->table('coupons')->where('code', strtoupper($code))->where('is_active', 1)->get()->getRowArray();
        if (! $coupon) return ['ok' => false, 'error' => 'Invalid coupon code.'];
        if ($coupon['ends_at'] && strtotime($coupon['ends_at']) < time()) return ['ok' => false, 'error' => 'Coupon has expired.'];

        $promo = $this->db->table('promotions')->where('id', $coupon['promotion_id'])->where('is_active', 1)->get()->getRowArray();
        if (! $promo) return ['ok' => false, 'error' => 'Promotion no longer active.'];

        $this->db->table('cart_applied_promotions')->where('cart_id', $cart['id'])->where('promotion_id', $promo['id'])->delete();
        $this->db->table('cart_applied_promotions')->insert([
            'cart_id'         => $cart['id'],
            'promotion_id'    => $promo['id'],
            'coupon_id'       => $coupon['id'],
            'coupon_code'     => $coupon['code'],
            'discount_amount' => 0, // calculated on recalc
        ]);
        $this->recalculate((int) $cart['id']);
        return ['ok' => true, 'message' => 'Coupon applied!'];
    }

    public function removeCoupon(string $code): array
    {
        $cart = $this->getCurrentCart();
        if (! $cart) return ['ok' => false, 'error' => 'No active cart.'];
        $this->db->table('cart_applied_promotions')
            ->where('cart_id', $cart['id'])
            ->where('coupon_code', strtoupper($code))
            ->delete();
        $this->recalculate((int) $cart['id']);
        return ['ok' => true, 'message' => 'Coupon removed.'];
    }

    /**
     * Promotions to surface on the cart page: active + within window + show_in_widget=1.
     * Each entry: {id, name, banner_text, type, rules, rewards, code, min_cart, qualifies}
     */
    public function availablePromos(): array
    {
        $cart = $this->getCurrentCart();
        $subtotal = (int) ($cart['subtotal'] ?? 0);

        $rows = $this->db->table('promotions p')
            ->select('p.id, p.name, p.banner_text, p.type, p.rules, p.rewards, p.requires_coupon, c.code')
            ->join('coupons c', 'c.promotion_id = p.id AND c.is_active = 1', 'left')
            ->where('p.is_active', 1)
            ->where('p.show_in_widget', 1)
            ->groupStart()
                ->where('p.starts_at IS NULL', null, false)
                ->orWhere('p.starts_at <=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->groupStart()
                ->where('p.ends_at IS NULL', null, false)
                ->orWhere('p.ends_at >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('p.priority', 'ASC')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $rules   = json_decode($r['rules']   ?? '{}', true) ?: [];
            $rewards = json_decode($r['rewards'] ?? '{}', true) ?: [];
            $minCart = (int) ($rules['min_cart'] ?? 0);
            $out[] = [
                'id'         => (int) $r['id'],
                'name'       => $r['name'],
                'banner'     => $r['banner_text'] ?: self::describeReward($r['type'], $rewards, $minCart),
                'type'       => $r['type'],
                'min_cart'   => $minCart,
                'code'       => $r['code'],
                'requires_coupon' => (int) $r['requires_coupon'],
                'qualifies'  => $minCart === 0 || $subtotal >= $minCart,
                'needs_more' => $minCart > 0 ? max(0, $minCart - $subtotal) : 0,
                'value'      => (int) ($rewards['value'] ?? 0),
            ];
        }
        return $out;
    }

    protected static function describeReward(string $type, array $rewards, int $minCart): string
    {
        $r = function_exists('kb_money_short') ? 'kb_money_short' : null;
        $money = function ($p) use ($r) {
            if ($r) return $r((int) $p);
            return '₹' . number_format(round($p / 100));
        };
        $val = (int) ($rewards['value'] ?? 0);
        $line = match ($type) {
            'percent_off'    => "{$val}% off",
            'flat_off'       => $money($val) . " off",
            'free_shipping'  => 'FREE shipping',
            'free_gift'      => 'Free gift',
            'cart_threshold' => 'Threshold reward',
            default          => 'Special offer',
        };
        if ($minCart > 0) $line .= ' on orders ' . $money($minCart) . '+';
        return $line;
    }

    public function clearForOrder(int $cartId): void
    {
        $this->db->table('cart_items')->where('cart_id', $cartId)->delete();
        $this->db->table('cart_applied_promotions')->where('cart_id', $cartId)->delete();
        $this->recalculate($cartId);
    }

    public function getCartWithItems(): array
    {
        $cart = $this->getCurrentCart(true);
        $items = $this->db->table('cart_items ci')
            ->join('products p', 'p.id = ci.product_id')
            ->join('product_variants v', 'v.id = ci.variant_id')
            ->select('ci.*, p.slug AS product_slug, p.name AS product_name, p.hero_image, v.name AS variant_name, p.type AS product_type')
            ->where('ci.cart_id', $cart['id'])
            ->orderBy('ci.id')
            ->get()->getResultArray();
        $promos = $this->db->table('cart_applied_promotions')
            ->where('cart_id', $cart['id'])->get()->getResultArray();
        return ['cart' => $cart, 'items' => $items, 'promotions' => $promos];
    }

    public function recalculate(int $cartId): void
    {
        $items = $this->db->table('cart_items')->where('cart_id', $cartId)->get()->getResultArray();
        $subtotal = 0;
        $itemCount = 0;
        foreach ($items as $i) {
            $subtotal += (int) $i['line_total'];
            $itemCount += (int) $i['qty'];
        }

        // Apply promotions
        $discount = 0;
        $promos = $this->db->table('cart_applied_promotions cap')
            ->join('promotions pr', 'pr.id = cap.promotion_id')
            ->where('cap.cart_id', $cartId)
            ->where('pr.is_active', 1)
            ->select('cap.*, pr.type AS p_type, pr.rules, pr.rewards')
            ->get()->getResultArray();

        foreach ($promos as $p) {
            $rules = json_decode($p['rules'] ?? '{}', true) ?: [];
            $rewards = json_decode($p['rewards'] ?? '{}', true) ?: [];
            if (! empty($rules['min_cart']) && $subtotal < (int) $rules['min_cart']) {
                continue; // doesn't qualify
            }
            $lineDiscount = 0;
            if ($p['p_type'] === 'percent_off') {
                $pct = (int) ($rewards['value'] ?? 0);
                $lineDiscount = (int) round($subtotal * $pct / 100);
                if (! empty($rewards['max_discount'])) $lineDiscount = min($lineDiscount, (int) $rewards['max_discount']);
            } elseif ($p['p_type'] === 'flat_off') {
                $lineDiscount = min((int) ($rewards['value'] ?? 0), $subtotal);
            }
            if ($lineDiscount > 0) {
                $this->db->table('cart_applied_promotions')->where('id', $p['id'])->update(['discount_amount' => $lineDiscount]);
                $discount += $lineDiscount;
            }
        }

        // Shipping
        $shipping = 0;
        $freeShippingThreshold = (int) $this->setting('shipping', 'free_shipping_threshold', 99900);
        $defaultShipping       = (int) $this->setting('shipping', 'default_charge', 7900);
        if ($itemCount > 0) {
            $hasFreeShip = $this->db->table('cart_applied_promotions cap')
                ->join('promotions pr', 'pr.id = cap.promotion_id')
                ->where('cap.cart_id', $cartId)->where('pr.type', 'free_shipping')->countAllResults() > 0;
            if (($subtotal - $discount) >= $freeShippingThreshold || $hasFreeShip) {
                $shipping = 0;
            } else {
                $shipping = $defaultShipping;
            }
        }

        // No GST adds here — prices are inclusive in seeded tax_classes; line tax is computed at invoice time.
        $tax = 0;
        $grand = max(0, $subtotal - $discount) + $shipping + $tax;

        $this->db->table('carts')->where('id', $cartId)->update([
            'subtotal'       => $subtotal,
            'discount_total' => $discount,
            'tax_total'      => $tax,
            'shipping_total' => $shipping,
            'grand_total'    => $grand,
            'item_count'     => $itemCount,
        ]);
        session()->set('cart_count', $itemCount);
    }

    public function mergeOnLogin(int $userId): void
    {
        // Move anonymous cart contents into user cart, prefer user cart if exists
        $anon = $this->anonId();
        $userCart = $this->db->table('carts')->where('user_id', $userId)->orderBy('updated_at', 'DESC')->limit(1)->get()->getRowArray();
        $guestCart = $this->db->table('carts')->where('user_id', null)->where('anon_id', $anon)->orderBy('updated_at', 'DESC')->limit(1)->get()->getRowArray();
        if (! $guestCart) return;

        if (! $userCart) {
            $this->db->table('carts')->where('id', $guestCart['id'])->update(['user_id' => $userId]);
            return;
        }
        $items = $this->db->table('cart_items')->where('cart_id', $guestCart['id'])->get()->getResultArray();
        foreach ($items as $it) {
            $existing = $this->db->table('cart_items')
                ->where(['cart_id' => $userCart['id'], 'variant_id' => $it['variant_id']])->get()->getRowArray();
            if ($existing) {
                $this->db->table('cart_items')->where('id', $existing['id'])->update([
                    'qty' => (int) $existing['qty'] + (int) $it['qty'],
                    'line_total' => ((int) $existing['qty'] + (int) $it['qty']) * (int) $existing['unit_price'],
                ]);
            } else {
                $this->db->table('cart_items')->insert(array_merge($it, ['cart_id' => $userCart['id'], 'id' => null]));
            }
        }
        $this->db->table('cart_items')->where('cart_id', $guestCart['id'])->delete();
        $this->db->table('carts')->where('id', $guestCart['id'])->delete();
        $this->recalculate((int) $userCart['id']);
    }

    /**
     * Classifies cart contents and returns which payment methods are eligible,
     * with human-readable reasons for any that are disabled.
     *
     * Used by the checkout page to render the right payment options, by
     * cart-summary nudges, and by order-confirmation emails.
     *
     * Returns:
     * [
     *   'cart_total' => int (paise),
     *   'composition' => ['physical' => N, 'digital' => N, 'offline' => N, 'recurring' => N, 'affiliate' => N],
     *   'flags'       => ['has_physical','has_digital','has_offline', ...],
     *   'methods'     => [
     *      'razorpay'      => ['available'=>true,  'label'=>..., 'reason'=>null],
     *      'cod'           => ['available'=>false, 'label'=>..., 'reason'=>'Digital products require pre-payment'],
     *      'partial_venue' => ['available'=>true,  'label'=>..., 'advance'=>40000, 'balance_at'=>'venue'],
     *      ...
     *   ],
     * ]
     */
    public function getPaymentEligibility(): array
    {
        $cart  = $this->getCurrentCart();
        if (! $cart) return ['methods' => [], 'composition' => [], 'flags' => [], 'cart_total' => 0];

        $items = $this->db->table('cart_items ci')
            ->join('products p', 'p.id = ci.product_id')
            ->select('ci.qty, ci.line_total, p.type AS p_type')
            ->where('ci.cart_id', $cart['id'])
            ->get()->getResultArray();

        // Classify
        $physicalTypes  = ['simple','variable','bundle'];
        $digitalTypes   = ['digital','course','webinar'];     // anything that's instantly accessible
        $offlineTypes   = ['meetup','service'];               // physical attendance required
        $recurringTypes = ['tuition','membership','subscription'];
        $affiliateTypes = ['affiliate'];

        $composition = ['physical'=>0,'digital'=>0,'offline'=>0,'recurring'=>0,'affiliate'=>0];
        foreach ($items as $it) {
            if     (in_array($it['p_type'], $physicalTypes,  true)) $composition['physical']++;
            elseif (in_array($it['p_type'], $digitalTypes,   true)) $composition['digital']++;
            elseif (in_array($it['p_type'], $offlineTypes,   true)) $composition['offline']++;
            elseif (in_array($it['p_type'], $recurringTypes, true)) $composition['recurring']++;
            elseif (in_array($it['p_type'], $affiliateTypes, true)) $composition['affiliate']++;
        }

        $flags = [
            'has_physical'  => $composition['physical']  > 0,
            'has_digital'   => $composition['digital']   > 0,
            'has_offline'   => $composition['offline']   > 0,
            'has_recurring' => $composition['recurring'] > 0,
            'has_affiliate' => $composition['affiliate'] > 0,
            'is_pure_physical' => $composition['physical'] > 0 && $composition['digital'] === 0 && $composition['offline'] === 0 && $composition['recurring'] === 0,
            'is_pure_digital'  => $composition['digital']  > 0 && $composition['physical'] === 0 && $composition['offline'] === 0,
            'is_pure_offline'  => $composition['offline']  > 0 && $composition['physical'] === 0 && $composition['digital']  === 0 && $composition['recurring'] === 0,
            'is_mixed'         => count(array_filter($composition)) > 1,
        ];

        $grandTotal = (int) $cart['grand_total'];

        // COD settings
        $codEnabled = (bool) $this->setting('cod', 'enabled',         '1');
        $codMin     = (int)  $this->setting('cod', 'min_order',       0);
        $codMax     = (int)  $this->setting('cod', 'max_order',       1000000);
        $codPartial = (bool) $this->setting('cod', 'partial_enabled', '1');

        // Part-pay (online classes / meetups) — tunable
        $venueAdvancePct = (int) $this->setting('venue_pay', 'advance_pct', 20);
        $venueAdvanceMin = (int) $this->setting('venue_pay', 'advance_min', 10000);
        $venueAdvanceMax = (int) $this->setting('venue_pay', 'advance_max', 100000);
        $venueAdvance    = max($venueAdvanceMin, min($venueAdvanceMax, (int) ceil($grandTotal * $venueAdvancePct / 100)));
        $venueBalanceAt  = $flags['has_offline'] ? ($composition['offline'] > 0 ? 'venue' : 'class') : 'none';

        $methods = [];

        // ---- Online instant gateways: always available
        $methods['razorpay'] = [
            'available' => true,
            'label'     => 'UPI / Card / Netbanking',
            'sub'       => 'Pay ' . self::money($grandTotal) . ' now via Razorpay',
            'icon'      => '💳',
            'reason'    => null,
        ];
        $methods['phonepe'] = [
            'available' => true,
            'label'     => 'PhonePe',
            'sub'       => 'Pay ' . self::money($grandTotal) . ' via PhonePe wallet/UPI',
            'icon'      => '📱',
            'reason'    => null,
        ];

        // ---- Cash on Delivery: only pure physical cart
        $codOK = $codEnabled && $flags['is_pure_physical'] && $grandTotal >= $codMin && $grandTotal <= $codMax;
        $codReason = null;
        if (! $codEnabled)                        $codReason = 'COD is currently disabled.';
        elseif ($flags['has_digital'])            $codReason = 'Digital products require pre-payment.';
        elseif ($flags['has_offline'])            $codReason = 'In-person bookings cannot be COD — try Part-pay (book seat + pay at venue).';
        elseif ($flags['has_recurring'])          $codReason = 'Subscriptions require a payment method on file.';
        elseif ($grandTotal < $codMin)            $codReason = 'COD available on orders above ' . self::money($codMin) . '.';
        elseif ($grandTotal > $codMax)            $codReason = 'COD available on orders up to ' . self::money($codMax) . '.';
        $methods['cod'] = [
            'available' => $codOK,
            'label'     => 'Cash on Delivery',
            'sub'       => $codOK ? 'Pay ₹0 now · pay the courier at your door' : null,
            'icon'      => '📦',
            'reason'    => $codReason,
        ];

        // ---- Partial COD: pure physical + setting enabled
        $partialCodOK = $codOK && $codPartial;
        $methods['partial_cod'] = [
            'available' => $partialCodOK,
            'label'     => 'Partial COD (' . $venueAdvancePct . '% online, rest at door)',
            'sub'       => $partialCodOK ? 'Pay ' . self::money($venueAdvance) . ' now · ' . self::money($grandTotal - $venueAdvance) . ' to the courier' : null,
            'icon'      => '🚪',
            'advance'   => $venueAdvance,
            'balance'   => $grandTotal - $venueAdvance,
            'balance_at'=> 'delivery',
            'reason'    => $partialCodOK ? null : ($codReason ?: 'Partial COD requires a fully physical cart.'),
        ];

        // ---- Partial at venue: offline-only cart (meetup / service / in-person tuition)
        $venueOK = $flags['has_offline'] && ! $flags['has_physical'] && ! $flags['has_digital'];
        $venueReason = null;
        if (! $flags['has_offline'])     $venueReason = 'Cart has no offline class / meetup / service.';
        elseif ($flags['has_physical'])  $venueReason = 'Cart contains a physical kit — that needs delivery payment separately.';
        elseif ($flags['has_digital'])   $venueReason = 'Cart contains a digital item — that needs pre-payment.';
        $methods['partial_venue'] = [
            'available'  => $venueOK,
            'label'      => 'Book seat now, balance at the ' . $venueBalanceAt,
            'sub'        => $venueOK ? 'Pay ' . self::money($venueAdvance) . ' to confirm · ' . self::money($grandTotal - $venueAdvance) . ' at the ' . $venueBalanceAt : null,
            'icon'       => '🎟️',
            'advance'    => $venueAdvance,
            'balance'    => $grandTotal - $venueAdvance,
            'balance_at' => $venueBalanceAt,
            'reason'     => $venueReason,
        ];

        return [
            'cart_total'  => $grandTotal,
            'composition' => $composition,
            'flags'       => $flags,
            'methods'     => $methods,
        ];
    }

    /** Tiny formatter — same as kb_money helper but doesn't require it loaded. */
    private static function money(int $paise): string
    {
        return '₹' . number_format(round($paise / 100));
    }

    public function setting(string $group, string $key, $default = null)
    {
        $row = $this->db->table('settings')->where('group_key', $group)->where('key', $key)->get()->getRow();
        if (! $row) return $default;
        return $row->value !== null && $row->value !== '' ? $row->value : $default;
    }

    protected function anonId(): string
    {
        $req = Services::request();
        $cookie = $req->getCookie('kb_anon');
        if ($cookie) return $cookie;
        // Cookie not yet set (first request); use session id as fallback
        return 'sess_' . session_id();
    }
}
