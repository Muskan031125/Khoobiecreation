<?php

namespace App\Libraries;

use Config\Database;

/**
 * On `order.paid`, populates `order_items.partner_id` from `products.partner_id`,
 * computes per-partner commission, and notifies each partner with the items
 * they need to ship.
 */
class PartnerFulfillmentService
{
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    /**
     * Returns array of [partner_id => ['partner'=>row, 'items'=>[], 'subtotal'=>int, 'commission'=>int]]
     */
    public function splitOrderByPartner(int $orderId): array
    {
        // Hydrate items with their partner_id (denormalised onto order_items at order-time)
        $items = $this->db->table('order_items oi')
            ->select('oi.*, p.partner_id, p.name AS product_name')
            ->join('products p', 'p.id = oi.product_id')
            ->where('oi.order_id', $orderId)
            ->where('p.partner_id IS NOT NULL', null, false)
            ->get()->getResultArray();

        $byPartner = [];
        foreach ($items as $it) {
            $pid = (int) $it['partner_id'];
            if (! $pid) continue;
            $byPartner[$pid]['items'][]   = $it;
            $byPartner[$pid]['subtotal']  = ($byPartner[$pid]['subtotal'] ?? 0) + (int) $it['line_total'];
            // Stamp partner_id on the order_item itself if not already
            if (empty($it['partner_id'])) {
                $this->db->table('order_items')->where('id', $it['id'])->update(['partner_id' => $pid]);
            }
        }

        // Attach partner row + commission %
        foreach ($byPartner as $pid => &$bucket) {
            $partner = $this->db->table('partners')->where('id', $pid)->get()->getRowArray();
            $bucket['partner']    = $partner;
            $commPct              = (float) ($partner['commission_pct'] ?? 0);
            $bucket['commission'] = (int) round($bucket['subtotal'] * $commPct / 100);
            $bucket['payout']     = $bucket['subtotal'] - $bucket['commission'];
        }
        return $byPartner;
    }

    /**
     * On `order.paid`, notify each partner with their fulfillment slice.
     * Returns count of partners notified.
     */
    public function notifyPartnersOnPaid(int $orderId): int
    {
        $split = $this->splitOrderByPartner($orderId);
        if (empty($split)) return 0;

        $order = $this->db->table('orders')->where('id', $orderId)->get()->getRowArray();
        $notif = new \App\Libraries\Notifications\NotificationService();
        $count = 0;

        foreach ($split as $pid => $bucket) {
            $p = $bucket['partner'];
            $itemsList = "";
            foreach ($bucket['items'] as $i) {
                $itemsList .= "- {$i['product_name']} × {$i['qty']}\n";
            }
            $payload = [
                'partner_name'  => $p['company_name'],
                'order_number'  => $order['order_number'],
                'customer_name' => $order['name'],
                'shipping'      => $order['shipping_address'],
                'items_text'    => $itemsList,
                'payout'        => $bucket['payout'],
                'commission'    => $bucket['commission'],
                'portal_url'    => rtrim(base_url(), '/') . '/partner/orders',
            ];
            if (! empty($p['email'])) {
                $notif->send('email', $p['email'], 'partner.new_order', $payload, null, 'order', $orderId);
                $count++;
            }
            if (! empty($p['phone'])) {
                $notif->send('whatsapp', $p['phone'], 'partner.new_order', $payload, null, 'order', $orderId);
            }
        }
        return $count;
    }
}
