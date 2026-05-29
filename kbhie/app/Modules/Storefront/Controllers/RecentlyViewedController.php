<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\RecentlyViewedService;

class RecentlyViewedController extends BaseStoreController
{
    public function index()
    {
        $svc   = new RecentlyViewedService();
        // Pull the full history (service caps at 20)
        $items = $svc->list(50);

        return $this->view('App\Modules\Storefront\Views\recently_viewed', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'Recently Viewed — Krafty Khoobie',
                'description' => 'Products you have looked at recently.',
            ]),
            'items' => $items,
        ]);
    }

    public function clear()
    {
        session()->remove('recently_viewed');
        if ($this->request->isAJAX()) return $this->response->setJSON(['ok' => true]);
        return redirect()->to('/recently-viewed');
    }
}
