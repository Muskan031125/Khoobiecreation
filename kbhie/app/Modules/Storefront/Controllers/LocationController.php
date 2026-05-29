<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\LocationService;

class LocationController extends BaseStoreController
{
    public function set()
    {
        $city     = trim((string) $this->request->getPost('city'));
        $locality = trim((string) $this->request->getPost('locality'));
        $pincode  = trim((string) $this->request->getPost('pincode'));
        if (! $city) return $this->response->setJSON(['ok' => false, 'error' => 'City required']);

        $rec = LocationService::set($city, $locality ?: null, $pincode ?: null);
        return $this->response->setJSON(['ok' => true, 'location' => $rec, 'label' => LocationService::label()]);
    }

    public function clear()
    {
        LocationService::clear();
        return $this->response->setJSON(['ok' => true]);
    }
}
