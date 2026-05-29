<?php
// Type-aware CTA labels — enrol-only types collapse to ONE primary CTA → /enrol/{vid}
$type = $product['type'] ?? 'simple';
$isEnrolOnly = in_array($type, \App\Libraries\Cart\CartService::ENROL_ONLY_TYPES, true);

// Affiliate: use the actual marketplace name for the CTA ("Buy on Amazon" beats "View on Partner").
$affiliatePartner = null;
if ($type === 'affiliate') {
    $aff = \Config\Database::connect()->table('affiliate_links')
        ->select('partner_name')->where('product_id', $product['id'])->where('is_active', 1)
        ->orderBy('IFNULL(price_at_partner, 999999999)', 'ASC', false)
        ->limit(1)->get()->getRowArray();
    $affiliatePartner = $aff['partner_name'] ?? 'partner';
}

$ctaLabels = match ($type) {
    'course'     => ['add' => null, 'buy' => 'Enrol Now'],
    'tuition'    => ['add' => null, 'buy' => 'Enrol — Book trial'],
    'meetup'     => ['add' => null, 'buy' => 'Reserve seat'],
    'service'    => ['add' => null, 'buy' => 'Book Slot'],
    'affiliate'  => ['add' => null, 'buy' => 'Buy on ' . $affiliatePartner . ' ↗'],
    'membership' => ['add' => null, 'buy' => 'Join Now'],
    'digital'    => ['add' => 'Add to cart',  'buy' => 'Buy & Download'],
    default      => ['add' => 'Add to cart',  'buy' => 'Buy Now'],
};

// Affiliate → outbound. Enrol-only → /enrol/{vid}. Otherwise → @click handler.
$affiliateUrl = ($type === 'affiliate') ? base_url('go/' . $product['slug']) : null;
$enrolUrl = ($isEnrolOnly && ! empty($product['variants'][0]['id']))
    ? base_url('enrol/' . (int) $product['variants'][0]['id'])
    : null;

$heroForBar    = $product['hero_image'] ?? null;
$heroForBarSrc = $heroForBar
    ? (preg_match('#^https?://#', $heroForBar) ? $heroForBar : base_url($heroForBar))
    : null;
?>

<!-- PDP sticky action bar — matches the site-wide floating-cart-bar aesthetic (rounded card, centered, shadow). Lives inside pdpState. -->
<div class="fixed bottom-0 inset-x-0 z-40 pointer-events-none"
     style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="pointer-events-auto mx-auto max-w-4xl mb-2 sm:mb-3 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-[0_-4px_24px_rgba(0,0,0,0.12)] ring-1 ring-slate-200 overflow-hidden">
            <div class="px-3 sm:px-4 py-2.5 sm:py-3 flex items-center gap-2 sm:gap-3 min-w-0">

                <!-- Thumb + product info -->
                <?php if ($heroForBarSrc): ?>
                    <img src="<?= esc($heroForBarSrc, 'attr') ?>" alt=""
                         class="hidden sm:block w-12 h-12 rounded-lg object-cover shrink-0 ring-1 ring-slate-200">
                <?php endif; ?>

                <div class="flex-1 min-w-0">
                    <div class="hidden sm:block text-xs text-slate-500 truncate font-semibold" title="<?= esc($product['name']) ?>">
                        <?= esc(character_limiter($product['name'], 40)) ?>
                    </div>
                    <div class="sm:hidden text-[10px] text-slate-500 truncate uppercase tracking-wide font-semibold">
                        <?= esc(character_limiter($product['name'], 26)) ?>
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 leading-tight flex items-baseline gap-2">
                        <span x-text="formatRupees(currentPrice)"><?= kb_money_short($initPrice ?? 0) ?></span>
                        <?php if (($initCompare ?? 0) > ($initPrice ?? 0)): ?>
                            <span x-show="currentCompare > currentPrice"
                                  class="text-xs text-slate-400 line-through font-semibold"
                                  x-text="formatRupees(currentCompare)"><?= kb_money_short($initCompare) ?></span>
                            <span x-show="currentCompare > currentPrice"
                                  class="hidden sm:inline text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded"
                                  x-text="discountPct + '% off'"><?= $initDiscount ?? '' ?>% off</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cart icon w/ live count -->
                <a href="<?= base_url('cart') ?>"
                   class="relative shrink-0 p-2 rounded-full bg-slate-100 hover:bg-slate-200 transition" aria-label="Open cart">
                    <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h11l3-8H6.4M7 13l-1.7 5h13.4M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm10 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/></svg>
                    <span data-cart-count
                          class="absolute -top-1 -right-1 bg-brand-500 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center"><?= esc($cart_count ?? 0) ?></span>
                </a>

                <!-- Secondary "Add to cart" (desktop visible label, mobile icon-only) -->
                <?php if ($ctaLabels['add']): ?>
                    <button type="button" @click="addToCart(false)"
                            class="hidden sm:inline-flex items-center px-3 py-2 rounded-lg border-2 border-slate-200 hover:border-brand-400 hover:bg-brand-50 text-slate-900 font-bold text-xs shrink-0 transition whitespace-nowrap">
                        <?= esc($ctaLabels['add']) ?>
                    </button>
                    <button type="button" @click="addToCart(false)"
                            class="sm:hidden p-2 rounded-lg border-2 border-slate-200 hover:bg-slate-50 shrink-0" aria-label="Add to cart">
                        <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h11l3-8H6.4M7 13l-1.7 5h13.4M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm10 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM12 8v8M8 12h8"/></svg>
                    </button>
                <?php endif; ?>

                <!-- Primary action: enrol-only types → /enrol; affiliate → outbound; else add-to-cart Buy Now -->
                <?php if ($affiliateUrl): ?>
                    <a href="<?= esc($affiliateUrl) ?>" target="_blank" rel="noopener nofollow sponsored"
                       class="inline-flex items-center justify-center px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs sm:text-sm shadow-cta shrink-0 whitespace-nowrap">
                        <?= esc($ctaLabels['buy']) ?>
                    </a>
                <?php elseif ($enrolUrl): ?>
                    <a href="<?= esc($enrolUrl) ?>"
                       class="inline-flex items-center justify-center px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs sm:text-sm shadow-cta shrink-0 whitespace-nowrap">
                        <?= esc($ctaLabels['buy']) ?> <span class="ml-1">&rarr;</span>
                    </a>
                <?php else: ?>
                    <button type="button" @click="addToCart(true)"
                            class="inline-flex items-center justify-center px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs sm:text-sm shadow-cta shrink-0 whitespace-nowrap">
                        <?= esc($ctaLabels['buy']) ?> <span class="ml-1">&rarr;</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Spacer so content above isn't covered by the floating bar -->
<div aria-hidden="true" class="h-20 sm:h-24"></div>
