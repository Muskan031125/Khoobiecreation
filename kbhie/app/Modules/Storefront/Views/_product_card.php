<?php
$hero = $p['hero_image'] ?? null;
$heroSrc = $hero ? (preg_match('#^https?://#', $hero) ? $hero : base_url($hero)) : null;
$price   = (int) ($p['price'] ?? 0);
$compare = (int) ($p['compare_at_price'] ?? 0);
$discount = ($compare > $price && $price > 0) ? (int) round((1 - $price / $compare) * 100) : 0;
$savings  = ($compare > $price) ? ($compare - $price) : 0;
$variantId = (int) ($p['variant_id'] ?? 0);
$productId = (int) ($p['id'] ?? 0);
$inCartQty = isset($cartVariants[$variantId]) ? (int) $cartVariants[$variantId] : 0;
$rating    = (float) ($p['rating_avg'] ?? 0);
$ratingCnt = (int) ($p['rating_count'] ?? 0);
$slug      = $p['slug'] ?? '#';
$inShortlist = isset($shortlistIds) && in_array($productId, (array) $shortlistIds, true);
$inCompare   = isset($compareIds)   && in_array($productId, (array) $compareIds,   true);

// Class / event / service / membership types skip cart — they go to /enrol/{vid}
$productType   = $p['type'] ?? 'simple';
$isEnrolOnly   = in_array($productType, ['course','tuition','meetup','service','membership','workshop','camp','webinar'], true);
$isAffiliate   = $productType === 'affiliate';

// Type-aware CTA: icon + verb + 1-line supporting microcopy. Built so the button
// SELLS the action ("Reserve seat · Limited spots") instead of being generic.
$enrolMeta = match ($productType) {
    'meetup'     => ['icon' => '📍', 'label' => 'Reserve seat',    'sub' => 'Limited spots',          'tone' => 'bg-rose-500 hover:bg-rose-600'],
    'tuition'    => ['icon' => '🎓', 'label' => 'Book FREE trial', 'sub' => 'No card · cancel anytime','tone' => 'bg-violet-600 hover:bg-violet-700'],
    'course'     => ['icon' => '▶',  'label' => 'Enrol & watch',   'sub' => 'Lifetime access',         'tone' => 'bg-indigo-600 hover:bg-indigo-700'],
    'service'    => ['icon' => '🤝', 'label' => 'Book slot',       'sub' => 'Pick your time',          'tone' => 'bg-emerald-600 hover:bg-emerald-700'],
    'membership' => ['icon' => '⭐', 'label' => 'Join — 7-day trial','sub' => 'Cancel anytime',         'tone' => 'bg-amber-500 hover:bg-amber-600'],
    'workshop',
    'camp'       => ['icon' => '🎨', 'label' => 'Reserve seat',    'sub' => 'Limited batch size',     'tone' => 'bg-rose-500 hover:bg-rose-600'],
    'webinar'    => ['icon' => '🎥', 'label' => 'Register free',   'sub' => 'Live, then on-demand',    'tone' => 'bg-sky-600 hover:bg-sky-700'],
    default      => ['icon' => '→',  'label' => 'Enrol Now',       'sub' => '',                        'tone' => 'bg-brand-500 hover:bg-brand-600'],
};

// Affiliate: prefer the cheapest marketplace name for the card label.
// One DB roundtrip per affiliate card — listings rarely have >24 cards on screen.
$affiliateLabel = null;
$affiliateUrl   = null;
if ($isAffiliate && ! empty($p['slug'])) {
    $cheapest = \Config\Database::connect()->table('affiliate_links')
        ->select('partner_name')->where('product_id', $productId)->where('is_active', 1)
        ->orderBy('IFNULL(price_at_partner, 999999999)', 'ASC', false)
        ->limit(1)->get()->getRowArray();
    $affiliateLabel = 'Buy on ' . ($cheapest['partner_name'] ?? 'marketplace');
    $affiliateUrl   = base_url('go/' . $p['slug']);
}

// ---- Status badge: pick ONE by precedence so the card doesn't get cluttered.
//      Stock & sales fields are optional — the badge just doesn't show if a caller
//      didn't include them in their SELECT.
$salesCount = (int) ($p['sales_count'] ?? 0);
$totalStock = isset($p['total_stock']) ? (int) $p['total_stock'] : null;
$publishedAt = $p['published_at'] ?? $p['created_at'] ?? null;
$isFeatured = (int) ($p['is_featured'] ?? 0);
$isNew = $publishedAt && (strtotime($publishedAt) >= strtotime('-14 days'));

$statusBadge = null;
if ($totalStock !== null && $totalStock > 0 && $totalStock <= 5) {
    // urgency wins — but only if we actually have inventory data and it's truly low
    $statusBadge = ['label' => '🔥 ONLY ' . $totalStock . ' LEFT', 'cls' => 'bg-rose-600 text-white'];
} elseif ($salesCount >= 50) {
    $statusBadge = ['label' => '🏆 BESTSELLER',                'cls' => 'bg-amber-500 text-white'];
} elseif ($rating >= 4.5 && $ratingCnt >= 20) {
    $statusBadge = ['label' => '★ HIGHLY RATED',                'cls' => 'bg-emerald-600 text-white'];
} elseif ($isNew) {
    $statusBadge = ['label' => '✨ NEW',                        'cls' => 'bg-sky-500 text-white'];
} elseif ($isFeatured) {
    $statusBadge = ['label' => '⭐ EDITOR\'S PICK',             'cls' => 'bg-violet-600 text-white'];
}
?>
<div class="kb-card group relative bg-white rounded-2xl border border-slate-200/70 overflow-hidden hover:shadow-xl hover:border-slate-300 transition-all duration-200 flex flex-col"
     x-data='{
         inCartQty: <?= $inCartQty ?>,
         busy: false,
         saved:  <?= $inShortlist ? 'true' : 'false' ?>,
         compared: <?= $inCompare ? 'true' : 'false' ?>,
         async toggleSave() { const j = await window.kbShortlist.toggle(<?= $productId ?>); if (j.ok) this.saved = j.in_list; },
         async toggleCompare() { const j = await window.kbCompare.toggle(<?= $productId ?>); if (j.ok) this.compared = j.in_list; }
     }'
     data-variant-id="<?= $variantId ?>"
     @cart:item-updated.window="if ($event.detail.variant_id === <?= $variantId ?>) inCartQty = $event.detail.qty"
     @shortlist:changed.window="if ($event.detail.product_id === <?= $productId ?>) saved = $event.detail.in_list"
     @compare:changed.window="if ($event.detail.product_id === <?= $productId ?>) compared = $event.detail.in_list">

    <!-- ============ Image ============ -->
    <a href="<?= base_url('product/' . $slug) ?>" class="relative block aspect-square bg-slate-100 overflow-hidden">
        <?php if ($heroSrc): ?>
            <img src="<?= esc($heroSrc) ?>" alt="<?= esc($p['name']) ?>" loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-5xl text-slate-300">🎁</div>
        <?php endif; ?>

        <!-- Top-left: discount badge + status badge stacked -->
        <div class="absolute top-2 left-2 flex flex-col items-start gap-1">
            <?php if ($discount > 0): ?>
                <span class="px-2 py-1 rounded-md bg-brand-500 text-white text-[11px] font-black shadow-md">
                    −<?= $discount ?>%
                </span>
            <?php endif; ?>
            <?php if ($statusBadge): ?>
                <span class="px-2 py-1 rounded-md text-[10px] font-black tracking-wide shadow-md <?= $statusBadge['cls'] ?>">
                    <?= $statusBadge['label'] ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Top-right: shortlist (heart) + compare icon buttons stacked -->
        <div class="absolute top-2 right-2 flex flex-col gap-1.5">
            <button type="button"
                    @click.prevent.stop="toggleSave()"
                    :title="saved ? 'Remove from shortlist' : 'Save to shortlist'"
                    :aria-pressed="saved"
                    :class="saved ? 'bg-rose-500 text-white ring-rose-500' : 'bg-white/95 text-slate-600 ring-slate-200 hover:text-rose-500 hover:ring-rose-400'"
                    class="w-8 h-8 rounded-full backdrop-blur shadow-md ring-1 inline-flex items-center justify-center transition">
                <svg class="w-4 h-4" :fill="saved ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
            <button type="button"
                    @click.prevent.stop="toggleCompare()"
                    :title="compared ? 'Remove from compare' : 'Add to compare'"
                    :aria-pressed="compared"
                    :class="compared ? 'bg-sky-600 text-white ring-sky-600' : 'bg-white/95 text-slate-600 ring-slate-200 hover:text-sky-600 hover:ring-sky-400'"
                    class="w-8 h-8 rounded-full backdrop-blur shadow-md ring-1 inline-flex items-center justify-center transition">
                <!-- Stacked-bars (compare) icon -->
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18M9 7v14M15 12v9M21 5v16"/></svg>
            </button>
        </div>

        <!-- Bottom-right: age pill (moved off top-right to make room for action icons) -->
        <?php if (! empty($p['age_min_years']) || ! empty($p['age_max_years'])): ?>
            <span class="absolute bottom-2 right-2 px-2 py-1 rounded-md bg-white/95 backdrop-blur text-slate-700 text-[10px] font-bold uppercase tracking-wide shadow-sm">
                Ages <?= (int) ($p['age_min_years'] ?? 0) ?>–<?= (int) ($p['age_max_years'] ?? 0) ?>
            </span>
        <?php endif; ?>

        <!-- Bottom-left: small ✓ chip when in cart (the qty itself lives in the stepper, so no redundant text) -->
        <span x-show="inCartQty > 0" x-cloak
              class="absolute bottom-2 left-2 inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-500 text-white shadow-lg ring-2 ring-white"
              aria-label="In cart">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        </span>
    </a>

    <!-- ============ Body ============ -->
    <div class="p-3 sm:p-3.5 flex-1 flex flex-col">

        <!-- Rating (compact, above title — like Amazon/Flipkart pattern) -->
        <?php if ($rating > 0): ?>
            <div class="flex items-center gap-1 text-[11px] leading-none mb-1">
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-emerald-600 text-white font-bold">
                    <?= number_format($rating, 1) ?>
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                </span>
                <span class="text-slate-500"><?= $ratingCnt >= 1000 ? round($ratingCnt / 1000, 1) . 'k' : $ratingCnt ?> ratings</span>
            </div>
        <?php endif; ?>

        <!-- Title -->
        <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2 group-hover:text-brand-600 min-h-[2.5rem]">
            <a href="<?= base_url('product/' . $slug) ?>"><?= esc($p['name']) ?></a>
        </h3>

        <!-- Short description (desktop only — mobile hides for density) -->
        <?php if (! empty($p['short_desc'])): ?>
            <p class="mt-1 text-xs text-slate-500 leading-snug line-clamp-2 hidden sm:block min-h-[2rem]">
                <?= esc(character_limiter($p['short_desc'], 70)) ?>
            </p>
        <?php endif; ?>

        <!-- Price block -->
        <div class="mt-2 flex items-baseline gap-1.5 flex-wrap">
            <span class="text-lg sm:text-xl font-black text-slate-900 leading-none"><?= kb_money_short($price) ?></span>
            <?php if ($compare > $price): ?>
                <span class="text-xs text-slate-400 line-through leading-none"><?= kb_money_short($compare) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($savings > 0): ?>
            <div class="mt-1 text-[11px] font-bold text-emerald-700 leading-none">
                You save <?= kb_money_short($savings) ?>
            </div>
        <?php endif; ?>

        <?php
        // Membership discount preview — shows what Insider members would pay
        $memb = $membership ?? \App\Libraries\MembershipService::current();
        if (! empty($memb['active']) && $memb['discount_pct'] > 0 && $price > 0):
            $memberPrice = (int) round($price * (1 - $memb['discount_pct'] / 100));
        ?>
            <div class="mt-1 text-[11px] font-bold text-violet-700 leading-none">
                ⭐ Insider price: <?= kb_money_short($memberPrice) ?>
            </div>
        <?php endif; ?>

        <!-- ============ Footer (CTA) ============ -->
        <div class="mt-auto pt-3">

            <?php if ($isEnrolOnly): ?>
                <!-- Enrol-only types skip the cart → /enrol/{vid} express checkout.
                     Two-line CTA: bold action verb + supporting microcopy so the user
                     knows what they're committing to before they click. -->
                <a href="<?= base_url('enrol/' . $variantId) ?>"
                   class="group/cta block w-full px-3 py-2 rounded-lg text-white shadow-cta hover:shadow-md transition <?= $enrolMeta['tone'] ?>">
                    <span class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-1.5 min-w-0">
                            <span class="text-base leading-none shrink-0"><?= $enrolMeta['icon'] ?></span>
                            <span class="text-xs font-black uppercase tracking-wider truncate"><?= esc($enrolMeta['label']) ?></span>
                        </span>
                        <svg class="w-3.5 h-3.5 shrink-0 transition group-hover/cta:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                    </span>
                    <?php if (! empty($enrolMeta['sub'])): ?>
                        <span class="block text-[10px] font-medium opacity-90 mt-0.5 truncate"><?= esc($enrolMeta['sub']) ?></span>
                    <?php endif; ?>
                </a>
            <?php elseif ($isAffiliate && $affiliateUrl): ?>
                <!-- Affiliate: opens partner marketplace in a new tab. NOT added to cart, NO points awarded. -->
                <a href="<?= esc($affiliateUrl) ?>" target="_blank" rel="noopener nofollow sponsored"
                   class="w-full h-10 inline-flex items-center justify-center gap-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-wider shadow-cta hover:shadow-md transition">
                    <?= esc($affiliateLabel) ?>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M7 17 17 7M7 7h10v10"/></svg>
                </a>
            <?php else: ?>
                <!-- DEFAULT: cartable types — Add to cart -->
                <button type="button" x-show="inCartQty === 0" x-cloak
                        data-variant-id="<?= $variantId ?>"
                        data-product-name="<?= esc($p['name'], 'attr') ?>"
                        data-product-image="<?= esc($heroSrc ?: '', 'attr') ?>"
                        @click.prevent.stop="busy = true; window.kbCart.addFromButton($el).finally(() => busy = false)"
                        :disabled="busy"
                        class="kb-add-to-cart w-full h-10 inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 hover:bg-brand-500 text-white text-xs font-bold uppercase tracking-wider shadow-sm hover:shadow-md transition disabled:opacity-60">
                    <svg x-show="!busy" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h11l3-8H6.4M7 13l-1.7 5h13.4M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm10 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/></svg>
                    <span x-show="!busy">Add to cart</span>
                    <span x-show="busy" x-cloak class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        Adding…
                    </span>
                </button>
            <?php endif; ?>

            <!-- IN CART: compact stepper (left) + primary View Cart (right) — same height, balanced widths -->
            <div x-show="inCartQty > 0" x-cloak class="flex items-stretch gap-1.5">
                <!-- Stepper: matches button height (h-10), takes only what it needs -->
                <div class="flex items-stretch h-10 rounded-lg border-2 border-emerald-500 bg-white overflow-hidden shrink-0">
                    <button type="button"
                            @click.prevent.stop="busy = true; window.kbCart.setQty(<?= $variantId ?>, Math.max(0, inCartQty - 1)).finally(() => busy = false)"
                            :disabled="busy"
                            class="w-8 flex items-center justify-center text-emerald-700 hover:bg-emerald-50 font-bold text-lg leading-none disabled:opacity-50"
                            aria-label="Decrease">&minus;</button>
                    <span class="px-2 min-w-[1.75rem] flex items-center justify-center text-emerald-800 font-black text-sm tabular-nums" x-text="inCartQty"></span>
                    <button type="button"
                            @click.prevent.stop="busy = true; window.kbCart.setQty(<?= $variantId ?>, inCartQty + 1).finally(() => busy = false)"
                            :disabled="busy"
                            class="w-8 flex items-center justify-center text-emerald-700 hover:bg-emerald-50 font-bold text-lg leading-none disabled:opacity-50"
                            aria-label="Increase">+</button>
                </div>

                <!-- View Cart: primary action, flex-1 fills remaining -->
                <a href="<?= base_url('cart') ?>"
                   class="flex-1 h-10 inline-flex items-center justify-center gap-1 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold uppercase tracking-wider shadow-sm hover:shadow-md transition">
                    View cart
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
