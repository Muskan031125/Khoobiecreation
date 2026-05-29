<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\CompareService;

class CompareController extends BaseStoreController
{
    public function index()
    {
        $svc   = new CompareService();
        $items = $svc->list();
        return $this->view('App\Modules\Storefront\Views\compare', [
            'page'  => array_merge($this->data['page'], [
                'title'       => 'Compare Products — Krafty Khoobie',
                'description' => 'Side-by-side comparison of selected products.',
            ]),
            'items' => $items,
            'max'   => CompareService::MAX,
        ]);
    }

    public function toggle()
    {
        $pid = (int) $this->request->getPost('product_id');
        $res = (new CompareService())->toggle($pid);
        return $this->response->setJSON($res);
    }

    public function remove()
    {
        $pid = (int) $this->request->getPost('product_id');
        $res = (new CompareService())->remove($pid);
        if ($this->request->isAJAX()) return $this->response->setJSON($res);
        return redirect()->to('/compare');
    }

    public function clear()
    {
        $res = (new CompareService())->clear();
        if ($this->request->isAJAX()) return $this->response->setJSON($res);
        return redirect()->to('/compare');
    }
}
