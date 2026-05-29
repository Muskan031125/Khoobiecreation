<?php

namespace App\Modules\Partner\Controllers;

use Config\Database;

class InventoryController extends BasePartnerController
{
    public function index()
    {
        if (! $this->partner) return redirect()->to('/partner/login');
        $rows = Database::connect()->table('inventory i')
            ->select('i.*, v.sku AS variant_sku, v.name AS variant_name, p.name AS product_name, p.id AS product_id, w.name AS warehouse_name')
            ->join('product_variants v', 'v.id = i.variant_id')
            ->join('products p', 'p.id = v.product_id')
            ->join('warehouses w', 'w.id = i.warehouse_id', 'left')
            ->where('p.partner_id', $this->partner['id'])
            ->orderBy('p.name')->get()->getResultArray();
        return $this->view('App\Modules\Partner\Views\inventory_index', [
            'page' => ['title' => 'Inventory'], 'rows' => $rows,
        ]);
    }

    public function update()
    {
        if (! $this->partner) return redirect()->to('/partner/login');
        $inventoryId = (int) $this->request->getPost('inventory_id');
        $qty = (int) $this->request->getPost('qty_on_hand');
        $db = Database::connect();
        $row = $db->table('inventory i')
            ->join('product_variants v', 'v.id = i.variant_id')
            ->join('products p', 'p.id = v.product_id')
            ->where('i.id', $inventoryId)->where('p.partner_id', $this->partner['id'])
            ->select('i.*')->get()->getRowArray();
        if ($row) {
            $delta = $qty - (int) $row['qty_on_hand'];
            $db->table('inventory')->where('id', $inventoryId)->update(['qty_on_hand' => $qty]);
            $db->table('inventory_movements')->insert([
                'variant_id' => $row['variant_id'], 'warehouse_id' => $row['warehouse_id'],
                'change_qty' => $delta, 'balance_after' => $qty, 'reason' => 'adjustment',
                'user_id' => session('user')['id'] ?? null, 'note' => 'Partner-updated',
            ]);
        }
        return redirect()->to('/partner/inventory')->with('success', 'Inventory updated.');
    }
}
