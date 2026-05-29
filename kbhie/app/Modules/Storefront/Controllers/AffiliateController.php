<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\Tracking\TrackingService;
use CodeIgniter\Controller;
use Config\Database;

/**
 * Handles outbound redirects for affiliate-type products and tracks clicks.
 *   GET /go/<product-slug>
 *     → reads affiliate_links.outbound_url
 *     → increments click_count
 *     → fires AffiliateClick tracking event
 *     → 302 redirects browser
 */
class AffiliateController extends Controller
{
    public function go(string $slug)
    {
        $db = Database::connect();

        // ?m={id} pins to a specific marketplace row (the user clicked "Buy on Flipkart"
        // on a multi-marketplace product). Without it, default to the cheapest row.
        $marketplaceId = (int) ($this->request->getGet('m') ?? 0);

        $builder = $db->table('affiliate_links al')
            ->join('products p', 'p.id = al.product_id')
            ->select('al.id, al.outbound_url, al.partner_name, al.product_id, al.price_at_partner, p.name')
            ->where('p.slug', $slug)
            ->where('p.status', 'active')
            ->where('al.is_active', 1);

        if ($marketplaceId > 0) {
            $builder->where('al.id', $marketplaceId);
        } else {
            // Cheapest-first (NULL prices sort last via IFNULL trick); user lands on the best deal.
            $builder->orderBy('IFNULL(al.price_at_partner, 999999999)', 'ASC', false)
                    ->limit(1);
        }
        $row = $builder->get()->getRowArray();

        if (! $row) return redirect()->to('/shop');

        // Bump click counter
        $db->table('affiliate_links')->where('id', $row['id'])->set('click_count', 'click_count + 1', false)->update();

        // Mirror to tracking_events
        try {
            (new TrackingService())->captureEvent([
                'event_name' => 'AffiliateClick',
                'url'        => current_url(),
                'source'     => 'server',
                'custom_data'=> [
                    'partner'    => $row['partner_name'],
                    'product_id' => (int) $row['product_id'],
                    'product'    => $row['name'],
                ],
            ]);
        } catch (\Throwable $e) { /* swallow */ }

        // Add light source tagging to the outbound URL
        $url = $row['outbound_url'];
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'kbref=' . urlencode($slug);

        return redirect()->to($url, 302);
    }
}
