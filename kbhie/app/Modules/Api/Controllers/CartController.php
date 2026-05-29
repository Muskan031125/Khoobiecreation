<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\Cart\CartService;

class CartController extends BaseApiController
{
    public function get()
    {
        $cs = new CartService();
        $cart = $cs->getCartWithItems();
        $elig = $cs->getPaymentEligibility();
        return $this->ok([
            'cart'         => $cart['cart'],
            'items'        => $cart['items'],
            'promotions'   => $cart['promotions'] ?? [],
            'eligibility'  => $elig,
        ]);
    }

    public function add()
    {
        $vid = (int) $this->request->getJsonVar('variant_id') ?: (int) $this->request->getPost('variant_id');
        $qty = (int) ($this->request->getJsonVar('qty') ?: $this->request->getPost('qty') ?: 1);
        if (! $vid) return $this->fail('variant_id required', 422);
        $res = (new CartService())->add($vid, max(1, $qty));
        return $this->response->setJSON($res);
    }

    public function setQty()
    {
        $vid = (int) $this->request->getJsonVar('variant_id') ?: (int) $this->request->getPost('variant_id');
        $qty = (int) ($this->request->getJsonVar('qty') ?: $this->request->getPost('qty') ?: 0);
        if (! $vid) return $this->fail('variant_id required', 422);
        $res = (new CartService())->setQty($vid, max(0, $qty));
        return $this->response->setJSON($res);
    }

    public function applyCoupon()
    {
        $code = trim((string) ($this->request->getJsonVar('code') ?: $this->request->getPost('code')));
        if (! $code) return $this->fail('code required', 422);
        return $this->response->setJSON((new CartService())->applyCoupon($code));
    }
}
