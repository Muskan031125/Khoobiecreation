<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

class WarehouseZonesController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();
        $rows = $db->table('warehouse_zones z')
            ->select('z.*, w.name AS warehouse_name, w.city')
            ->join('warehouses w', 'w.id = z.warehouse_id')
            ->orderBy('z.priority', 'ASC')->orderBy('z.pincode_pattern')
            ->get()->getResultArray();
        $warehouses = $db->table('warehouses')->where('is_active', 1)->orderBy('is_default', 'DESC')->get()->getResultArray();
        return $this->view('App\Modules\Admin\Views\warehouse_zones', [
            'page' => ['title' => 'Warehouse Zones'],
            'rows' => $rows, 'warehouses' => $warehouses,
        ]);
    }

    public function save()
    {
        Database::connect()->table('warehouse_zones')->insert([
            'warehouse_id'    => (int) $this->request->getPost('warehouse_id'),
            'pincode_pattern' => trim((string) $this->request->getPost('pincode_pattern')),
            'priority'        => max(0, (int) $this->request->getPost('priority')),
            'estimated_days'  => max(1, (int) $this->request->getPost('estimated_days')),
        ]);
        return redirect()->to('/admin/warehouse-zones')->with('success', 'Zone added.');
    }

    public function delete($id)
    {
        Database::connect()->table('warehouse_zones')->where('id', (int) $id)->delete();
        return redirect()->to('/admin/warehouse-zones')->with('success', 'Zone removed.');
    }

    public function test()
    {
        $pin = trim((string) $this->request->getGet('pin'));
        if (! $pin) return $this->response->setJSON(['ok' => false, 'error' => 'pin required']);
        return $this->response->setJSON(['ok' => true, 'result' => \App\Libraries\WarehouseRoutingService::routeForPincode($pin)]);
    }
}
