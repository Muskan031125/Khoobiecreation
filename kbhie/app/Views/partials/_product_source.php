<?php
/**
 * Product-source label — a small pill that tells the buyer exactly
 * "who is fulfilling this" and "how it gets to you".
 *
 * Renders per product type:
 *   simple/variable/bundle (no partner)  → "🚚 Shipped by Khoobie"
 *   simple/variable/bundle (with partner) → "🚚 Shipped by {Partner Name}" or "📦 Sold by {Partner}, shipped from Khoobie"
 *   digital                              → "⚡ Instant download — no shipping"
 *   affiliate                            → "↗ Sold by {Partner} — outbound link"
 *   course                               → "🎬 On-demand video access"
 *   tuition                              → "💻 Live online — {city or 'remote'}"
 *   meetup                               → "📍 In-person at {City > Locality}"
 *   service                              → "🤝 1-on-1 booking — flexible modality"
 *   membership                           → "⭐ Recurring membership — cancel anytime"
 *
 * Pass the full $product row from PDP; for cards pass minimal {type, partner_id, …}.
 */
$type      = $product['type'] ?? 'simple';
$partnerId = (int) ($product['partner_id'] ?? 0);

$db = \Config\Database::connect();
$partner = null;
if ($partnerId) {
    $partner = $db->table('partners')->select('company_name, fulfillment_type')->where('id', $partnerId)->get()->getRowArray();
}

// For meetups, pull city/locality from the meetups table; for tuitions, pull modality
$loc = '';
if ($type === 'meetup') {
    $m = $db->table('meetups')->select('city, locality')->where('product_id', $product['id'])->get()->getRowArray();
    if ($m) $loc = trim(($m['locality'] ? $m['locality'] . ', ' : '') . $m['city'], ', ');
} elseif ($type === 'tuition') {
    $t = $db->table('tuitions')->select('modality')->where('product_id', $product['id'])->get()->getRowArray();
    $loc = $t['modality'] ?? 'online';
}

// For affiliate, pull EVERY active marketplace row (Amazon, Flipkart, Meesho, …)
// — one product may now list on multiple marketplaces.
$affiliateLinks = [];
if ($type === 'affiliate') {
    $affiliateLinks = $db->table('affiliate_links')
        ->select('partner_name, price_at_partner, price_updated_at')
        ->where('product_id', $product['id'])
        ->where('is_active', 1)
        ->orderBy('price_at_partner', 'ASC')
        ->get()->getResultArray();
}

// Compose the badge
$badge = match (true) {
    in_array($type, ['simple','variable','bundle']) && $partner && $partner['fulfillment_type'] === 'drop_ship'
        => ['icon' => '📦', 'text' => '<strong>Sold &amp; shipped by ' . esc($partner['company_name']) . '</strong>', 'cls' => 'bg-sky-50 text-sky-800 ring-sky-200'],
    in_array($type, ['simple','variable','bundle']) && $partner
        => ['icon' => '🚚', 'text' => '<strong>Sourced from ' . esc($partner['company_name']) . '</strong> · Shipped by Khoobie', 'cls' => 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
    in_array($type, ['simple','variable','bundle'])
        => ['icon' => '🚚', 'text' => '<strong>Shipped by Khoobie</strong> · Pan-India delivery 2-6 days', 'cls' => 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
    $type === 'digital'
        => ['icon' => '⚡', 'text' => '<strong>Instant download</strong> · Link emailed within seconds of payment', 'cls' => 'bg-violet-50 text-violet-800 ring-violet-200'],
    $type === 'affiliate' && count($affiliateLinks) > 0
        => (function () use ($affiliateLinks) {
            // Single marketplace → "Buy on Amazon · Price as listed on Amazon"
            // Multiple marketplaces → "Available on Amazon · Flipkart · Meesho — pick your favourite"
            $names = array_unique(array_map(fn($l) => $l['partner_name'], $affiliateLinks));
            if (count($names) === 1) {
                $name = esc(reset($names));
                return [
                    'icon' => '↗',
                    'text' => '<strong>Buy on ' . $name . '</strong> · Khoobie hand-picked, you check out on ' . $name,
                    'cls'  => 'bg-amber-50 text-amber-800 ring-amber-200',
                ];
            }
            return [
                'icon' => '↗',
                'text' => '<strong>Available on ' . esc(implode(' · ', $names)) . '</strong> · Pick the marketplace you prefer',
                'cls'  => 'bg-amber-50 text-amber-800 ring-amber-200',
            ];
        })(),
    $type === 'affiliate'
        => ['icon' => '↗', 'text' => '<strong>External link</strong> · Sold on partner marketplace', 'cls' => 'bg-amber-50 text-amber-800 ring-amber-200'],
    $type === 'course'
        => ['icon' => '🎬', 'text' => '<strong>On-demand video course</strong> · Lifetime access from any device', 'cls' => 'bg-rose-50 text-rose-800 ring-rose-200'],
    $type === 'tuition'
        => ['icon' => '💻', 'text' => '<strong>Live ' . esc($loc) . ' classes</strong> · weekly small-group sessions', 'cls' => 'bg-sky-50 text-sky-800 ring-sky-200'],
    $type === 'meetup'
        => ['icon' => '📍', 'text' => '<strong>In-person event</strong> at ' . esc($loc ?: 'venue TBC') . ' · capacity-limited', 'cls' => 'bg-amber-50 text-amber-800 ring-amber-200'],
    $type === 'service'
        => ['icon' => '🤝', 'text' => '<strong>1-on-1 private booking</strong> · pick your own slot', 'cls' => 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
    $type === 'membership'
        => ['icon' => '⭐', 'text' => '<strong>Recurring membership</strong> · cancel anytime', 'cls' => 'bg-violet-50 text-violet-800 ring-violet-200'],
    default => null,
};
if (! $badge) return;
?>
<div class="mt-4 inline-flex items-start gap-2 px-3 py-2 rounded-lg ring-1 <?= $badge['cls'] ?> text-xs sm:text-sm">
    <span class="text-base leading-tight"><?= $badge['icon'] ?></span>
    <span><?= $badge['text'] ?></span>
</div>
