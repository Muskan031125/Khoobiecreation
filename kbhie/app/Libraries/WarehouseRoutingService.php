<?php

namespace App\Libraries;

use Config\Database;

/**
 * Picks the best warehouse for an order based on the customer's pincode.
 * Falls back to the default warehouse if no zone matches.
 *
 * Usage:
 *   $w = WarehouseRoutingService::routeForPincode('400050');
 *   // returns ['warehouse_id' => 1, 'name' => 'Khoobie HQ', 'estimated_days' => 2]
 */
class WarehouseRoutingService
{
    public static function routeForPincode(string $pincode): array
    {
        $db = Database::connect();
        // Try longest pattern match first (more specific wins) by sorting on pattern length
        $zones = $db->query("
            SELECT z.warehouse_id, z.pincode_pattern, z.priority, z.estimated_days,
                   w.name, w.city, w.code
            FROM warehouse_zones z
            JOIN warehouses w ON w.id = z.warehouse_id
            WHERE w.is_active = 1 AND ? LIKE REPLACE(z.pincode_pattern, '%', '%')
            ORDER BY CHAR_LENGTH(z.pincode_pattern) DESC, z.priority ASC
            LIMIT 1
        ", [$pincode])->getRow();

        if ($zones) {
            return [
                'warehouse_id'   => (int) $zones->warehouse_id,
                'name'           => $zones->name,
                'city'           => $zones->city,
                'code'           => $zones->code,
                'estimated_days' => (int) $zones->estimated_days,
                'matched_zone'   => $zones->pincode_pattern,
            ];
        }

        // Default warehouse fallback
        $default = $db->table('warehouses')->where('is_default', 1)->where('is_active', 1)->get()->getRow();
        return [
            'warehouse_id'   => $default ? (int) $default->id : 1,
            'name'           => $default ? $default->name : 'Khoobie HQ',
            'city'           => $default->city ?? null,
            'code'           => $default->code ?? null,
            'estimated_days' => 5,
            'matched_zone'   => 'default',
        ];
    }

    /**
     * Split order_items by their best warehouse — supports per-line routing
     * (useful when some items are warehoused, others drop-shipped by partners).
     */
    public static function splitOrderByWarehouse(int $orderId, string $pincode): array
    {
        $db = Database::connect();
        $items = $db->table('order_items oi')
            ->select('oi.id, oi.product_id, oi.qty, p.partner_id, p.name AS product_name, par.fulfillment_type, par.company_name AS partner_name')
            ->join('products p', 'p.id = oi.product_id')
            ->join('partners par', 'par.id = p.partner_id', 'left')
            ->where('oi.order_id', $orderId)
            ->get()->getResultArray();

        $routed = self::routeForPincode($pincode);
        $splits = [];

        foreach ($items as $it) {
            if ($it['partner_id'] && ($it['fulfillment_type'] ?? '') === 'drop_ship') {
                $key = 'partner:' . $it['partner_id'];
                $splits[$key]['type'] = 'partner_dropship';
                $splits[$key]['label'] = 'Drop-ship by ' . $it['partner_name'];
            } else {
                $key = 'warehouse:' . $routed['warehouse_id'];
                $splits[$key]['type'] = 'warehouse';
                $splits[$key]['label'] = $routed['name'] . ' · ETA ' . $routed['estimated_days'] . 'd';
                $splits[$key]['warehouse'] = $routed;
            }
            $splits[$key]['items'][] = $it;
        }

        return $splits;
    }
}
