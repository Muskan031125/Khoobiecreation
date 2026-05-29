<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\ShortlistService;

class ShortlistController extends BaseStoreController
{
    public function index()
    {
        $svc   = new ShortlistService();
        $items = $svc->list();
        return $this->view('App\Modules\Storefront\Views\shortlist', [
            'page'  => array_merge($this->data['page'], [
                'title'       => 'Your Shortlist — Krafty Khoobie',
                'description' => 'Products you saved for later.',
            ]),
            'items' => $items,
        ]);
    }

    public function toggle()
    {
        $pid = (int) $this->request->getPost('product_id');
        $res = (new ShortlistService())->toggle($pid);
        return $this->response->setJSON($res);
    }

    public function remove()
    {
        $pid = (int) $this->request->getPost('product_id');
        $res = (new ShortlistService())->remove($pid);
        if ($this->request->isAJAX()) return $this->response->setJSON($res);
        return redirect()->to('/shortlist');
    }
}
