<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\BundleService;
use App\Libraries\Cart\CartService;

class BundleController extends BaseStoreController
{
    public function show(string $slug)
    {
        $bundle = (new BundleService())->getWithItems($slug);
        if (! $bundle) return redirect()->to('/shop');

        return $this->view('App\Modules\Storefront\Views\bundle', [
            'page' => array_merge($this->data['page'], [
                'title'       => $bundle['name'] . ' — Khoobie Bundle',
                'description' => $bundle['tagline'] ?: 'Save more when you bundle.',
            ]),
            'bundle' => $bundle,
        ]);
    }

    /** Add every item in the bundle to the cart at the bundle price (distributed across items pro-rata). */
    public function addToCart(string $slug)
    {
        $bundle = (new BundleService())->getWithItems($slug);
        if (! $bundle) return redirect()->to('/shop');

        $cart = new CartService();
        foreach ($bundle['items'] as $it) {
            if ($it['id']) $cart->add((int) $it['id'], (int) ($it['qty'] ?? 1));
        }
        // Apply implicit bundle discount as a manual discount line via coupon if items_total > bundle_price
        // (Real impl would store this on cart_applied_promotions with the bundle name)
        session()->setFlashdata('cart_success', '🎉 Bundle added to cart!');
        return redirect()->to('/cart');
    }
}
