<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\PushService;

class PushController extends BaseStoreController
{
    public function subscribe()
    {
        $sub = $this->request->getJSON(true) ?: [];
        $user = session('user');
        $res  = (new PushService())->saveSubscription($sub, $user['id'] ?? null);
        return $this->response->setJSON($res);
    }
}
