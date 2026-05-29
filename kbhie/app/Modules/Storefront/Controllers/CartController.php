<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\Cart\CartService;

class CartController extends BaseStoreController
{
    public function index()
    {
        $svc  = new CartService();

        // Auto-apply WELCOME10 if visitor arrived via /r/{code} and hasn't already used a coupon.
        // The referral cookie is dropped by ReferralController::land.
        $refCookie = $this->request->getCookie('kb_ref');
        if ($refCookie) {
            $current = $svc->getCurrentCart();
            $alreadyApplied = $current ? \Config\Database::connect()->table('cart_applied_promotions')
                ->where('cart_id', $current['id'])->countAllResults() : 0;
            if (! $alreadyApplied && ! session('referral_coupon_applied')) {
                $svc->applyCoupon('WELCOME10');
                session()->set('referral_coupon_applied', 1);
                session()->setFlashdata('cart_success', '🎉 Welcome! Your friend\'s 10% off has been applied.');
            }
        }

        $cart = $svc->getCartWithItems();

        // Surface available offers to the cart page (banner strip + tiered nudge)
        $available  = $svc->availablePromos();
        $freeShipAt = (int) $svc->setting('shipping', 'free_shipping_threshold', 99900);

        // Suggested cross-sell products — bestsellers not already in the cart
        $variantIdsInCart = array_map(fn ($i) => (int) $i['variant_id'], $cart['items']);
        $db = \Config\Database::connect();
        $upsellBuilder = $db->table('products p')
            ->select("p.id, p.slug, p.name, p.hero_image, p.short_desc, p.rating_avg, p.rating_count,
                      p.sales_count, p.is_featured, p.published_at, p.age_min_years, p.age_max_years,
                      (SELECT id FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS variant_id,
                      (SELECT price FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS price,
                      (SELECT compare_at_price FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS compare_at_price,
                      (SELECT COALESCE(SUM(i.qty_on_hand), 0) FROM inventory i
                         JOIN product_variants v2 ON v2.id = i.variant_id WHERE v2.product_id = p.id) AS total_stock", false)
            ->where('p.status', 'active')
            ->orderBy('p.rating_count', 'DESC')
            ->limit(8);
        $upsells = array_filter($upsellBuilder->get()->getResultArray(), function ($p) use ($variantIdsInCart) {
            return ! in_array((int) ($p['variant_id'] ?? 0), $variantIdsInCart, true);
        });
        $upsells = array_slice(array_values($upsells), 0, 4);

        return $this->view('App\Modules\Storefront\Views\cart', [
            'page'             => array_merge($this->data['page'], ['title' => 'Your Cart — Krafty Khoobie']),
            'cart'             => $cart['cart'],
            'items'            => $cart['items'],
            'promotions'       => $cart['promotions'],
            'available_promos' => $available,
            'free_ship_at'     => $freeShipAt,
            'upsells'          => $upsells,
            'recentlyViewed'   => (new \App\Libraries\RecentlyViewedService())->list(8),
        ]);
    }

    public function applyCoupon()
    {
        $code = trim((string) $this->request->getPost('code'));
        if ($code === '') {
            if ($this->wantsJson()) return $this->response->setJSON(['ok' => false, 'error' => 'Enter a coupon code.']);
            return redirect()->to('/cart')->with('cart_error', 'Enter a coupon code.');
        }
        $res = (new CartService())->applyCoupon($code);
        if ($this->wantsJson()) return $this->response->setJSON($res);
        return $res['ok']
            ? redirect()->to('/cart')->with('cart_success', $res['message'] ?? 'Coupon applied.')
            : redirect()->to('/cart')->with('cart_error',  $res['error']   ?? 'Could not apply.');
    }

    public function removeCoupon()
    {
        $code = trim((string) $this->request->getPost('code'));
        $res = (new CartService())->removeCoupon($code);
        if ($this->wantsJson()) return $this->response->setJSON($res);
        return redirect()->to('/cart')->with('cart_success', 'Coupon removed.');
    }

    public function add()
    {
        $variantId = (int) $this->request->getPost('variant_id');
        $qty       = max(1, (int) $this->request->getPost('qty'));
        $res = (new CartService())->add($variantId, $qty);
        if ($this->wantsJson()) return $this->response->setJSON($res);
        return $res['ok'] ? redirect()->to('/cart') : redirect()->back()->with('error', $res['error']);
    }

    public function update()
    {
        $itemId = (int) $this->request->getPost('item_id');
        $qty    = (int) $this->request->getPost('qty');
        $res = (new CartService())->updateQty($itemId, $qty);
        if ($this->wantsJson()) return $this->response->setJSON($res);
        return redirect()->to('/cart');
    }

    public function remove()
    {
        $itemId = (int) $this->request->getPost('item_id');
        $res = (new CartService())->remove($itemId);
        if ($this->wantsJson()) return $this->response->setJSON($res);
        return redirect()->to('/cart');
    }

    public function mini()
    {
        $cart = (new CartService())->getCartWithItems();
        return view('App\Modules\Storefront\Views\_cart_mini', $cart);
    }

    /** Lightweight JSON for header cart badge + post-action refresh. */
    public function count()
    {
        $cart = (new CartService())->getCurrentCart();
        return $this->response->setJSON([
            'item_count'  => (int) ($cart['item_count']  ?? 0),
            'grand_total' => (int) ($cart['grand_total'] ?? 0),
        ]);
    }

    /** Set absolute qty by variant_id (powers product-card +/− stepper). */
    public function setQty()
    {
        $variantId = (int) $this->request->getPost('variant_id');
        $qty       = max(0, (int) $this->request->getPost('qty'));
        $res = (new CartService())->setQty($variantId, $qty);
        return $this->response->setJSON($res);
    }

    protected function wantsJson(): bool
    {
        $accept = (string) $this->request->getHeaderLine('Accept');
        return $this->request->isAJAX() || str_contains($accept, 'application/json');
    }
}
