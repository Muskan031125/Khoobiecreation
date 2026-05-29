<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php
$subtotal     = (int) ($cart['subtotal'] ?? 0);
$discountTot  = (int) ($cart['discount_total'] ?? 0);
$shippingTot  = (int) ($cart['shipping_total'] ?? 0);
$grandTotal   = (int) ($cart['grand_total'] ?? 0);
$freeShipAt   = (int) ($free_ship_at ?? 99900);
$freeShipPct  = $freeShipAt > 0 ? min(100, (int) round(($subtotal / $freeShipAt) * 100)) : 100;
$needsForFreeShip = max(0, $freeShipAt - $subtotal);
$success      = session()->getFlashdata('cart_success');
$err          = session()->getFlashdata('cart_error');
?>

<section class="py-5 sm:py-8 lg:py-12 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-6xl px-3 sm:px-4 lg:px-6">

        <!-- Breadcrumb + heading -->
        <nav class="text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-2">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <span class="text-slate-900 font-semibold">Your Cart</span>
        </nav>
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black">Your Cart <?php if (! empty($items)): ?><span class="text-slate-400 font-normal text-base">· <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?></span><?php endif; ?></h1>
            <a href="<?= base_url('shop') ?>" class="text-xs sm:text-sm text-brand-600 font-semibold hover:underline">&larr; Continue shopping</a>
        </div>

        <?php if ($success): ?>
            <div class="mt-3 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold"><?= esc($success) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="mt-3 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold"><?= esc($err) ?></div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="mt-8 bg-white rounded-2xl p-8 sm:p-10 text-center">
                <div class="text-5xl">🛒</div>
                <h2 class="mt-3 text-lg font-bold">Your cart is empty</h2>
                <p class="mt-1 text-slate-600">Browse our collections and pick something for the little ones.</p>
                <a href="<?= base_url('shop') ?>" class="mt-5 inline-block btn-primary">Start shopping &rarr;</a>
            </div>

        <?php else: ?>

        <!-- ====== Tiered nudge — always visible above the items ====== -->
        <div class="mt-4 bg-white rounded-2xl p-3 sm:p-4 ring-1 ring-slate-100">
            <div class="flex items-center justify-between gap-3 text-xs sm:text-sm">
                <?php if ($needsForFreeShip > 0): ?>
                    <span class="text-slate-700 font-semibold">
                        🚚 Add <span class="text-brand-600 font-black"><?= kb_money_short($needsForFreeShip) ?></span> more for <span class="text-emerald-700">FREE shipping</span>
                    </span>
                <?php else: ?>
                    <span class="text-emerald-700 font-bold">🎉 You've unlocked FREE shipping!</span>
                <?php endif; ?>
                <span class="text-[10px] sm:text-xs text-slate-500 shrink-0"><?= kb_money_short($subtotal) ?> / <?= kb_money_short($freeShipAt) ?></span>
            </div>
            <div class="mt-1.5 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all duration-700"
                     style="width: <?= $freeShipPct ?>%"></div>
            </div>

            <?php
            // Show the NEXT tier (first promo we don't yet qualify for, after free-shipping)
            $nextTier = null;
            foreach (($available_promos ?? []) as $promo) {
                if (! $promo['qualifies'] && $promo['needs_more'] > $needsForFreeShip && $promo['type'] !== 'free_shipping') {
                    $nextTier = $promo;
                    break;
                }
            }
            if ($nextTier): ?>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-3 text-xs sm:text-sm">
                    <span class="text-slate-700 font-semibold">
                        🎁 Add <span class="text-brand-600 font-black"><?= kb_money_short($nextTier['needs_more']) ?></span> more to unlock
                        <span class="text-amber-700"><?= esc($nextTier['banner']) ?></span>
                        <?php if ($nextTier['code']): ?>
                            with code <span class="font-mono bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded"><?= esc($nextTier['code']) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- ====== Available offers strip ====== -->
        <?php if (! empty($available_promos)): ?>
            <div class="mt-4">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">Available offers</div>
                <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                    <?php foreach ($available_promos as $promo): ?>
                        <div class="shrink-0 min-w-[240px] max-w-[280px] bg-white rounded-xl p-3 ring-1 <?= $promo['qualifies'] ? 'ring-emerald-200 bg-emerald-50/40' : 'ring-slate-200' ?>">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] uppercase tracking-wide font-bold <?= $promo['qualifies'] ? 'text-emerald-700' : 'text-slate-500' ?>">
                                    <?= $promo['qualifies'] ? '✓ Eligible' : '◌ Add ' . kb_money_short($promo['needs_more']) ?>
                                </span>
                                <?php if ($promo['code']): ?>
                                    <span class="font-mono text-[11px] bg-slate-900 text-white px-2 py-0.5 rounded"><?= esc($promo['code']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-1 font-bold text-sm text-slate-900 line-clamp-2"><?= esc($promo['banner']) ?></div>
                            <?php if ($promo['code'] && $promo['qualifies']): ?>
                                <form method="post" action="<?= base_url('cart/apply-coupon') ?>" class="mt-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="code" value="<?= esc($promo['code'], 'attr') ?>">
                                    <button type="submit" class="w-full text-xs font-bold text-brand-600 hover:text-brand-700">Apply &rarr;</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ====== Items + summary ====== -->
        <div class="mt-5 grid lg:grid-cols-[1fr_360px] gap-4 lg:gap-6">

            <!-- Line items -->
            <div class="bg-white rounded-2xl divide-y divide-slate-100 overflow-hidden">
                <?php foreach ($items as $it): ?>
                    <?php
                    $img = $it['hero_image'] ?? null;
                    $imgSrc = $img ? (preg_match('#^https?://#', $img) ? $img : base_url($img)) : null;
                    ?>
                    <div class="p-3 sm:p-4 flex gap-3 sm:gap-4 items-start">
                        <a href="<?= base_url('product/' . $it['product_slug']) ?>" class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg bg-slate-100 overflow-hidden shrink-0 flex items-center justify-center text-2xl">
                            <?php if ($imgSrc): ?>
                                <img src="<?= esc($imgSrc, 'attr') ?>" alt="<?= esc($it['product_name'], 'attr') ?>" class="w-full h-full object-cover">
                            <?php else: ?>🎁<?php endif; ?>
                        </a>
                        <div class="flex-1 min-w-0">
                            <a href="<?= base_url('product/' . $it['product_slug']) ?>" class="font-bold text-sm sm:text-base hover:text-brand-600 line-clamp-2">
                                <?= esc($it['product_name']) ?>
                            </a>
                            <?php if (! empty($it['variant_name']) && $it['variant_name'] !== 'Default'): ?>
                                <div class="text-xs text-slate-500 mt-0.5"><?= esc($it['variant_name']) ?></div>
                            <?php endif; ?>
                            <div class="text-xs sm:text-sm text-slate-600 mt-0.5"><?= kb_money((int) $it['unit_price']) ?> each</div>

                            <div class="mt-2 flex items-center gap-3 flex-wrap">
                                <!-- Qty stepper -->
                                <form method="post" action="<?= base_url('cart/update') ?>" class="flex items-center">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                                    <div class="flex items-center border-2 border-slate-200 rounded-lg overflow-hidden">
                                        <button name="qty" value="<?= max(0, $it['qty'] - 1) ?>"
                                                class="px-2.5 py-1 text-slate-600 hover:bg-slate-100 font-bold" aria-label="Decrease">&minus;</button>
                                        <span class="px-3 font-bold text-sm select-none min-w-[2rem] text-center"><?= (int) $it['qty'] ?></span>
                                        <button name="qty" value="<?= $it['qty'] + 1 ?>"
                                                class="px-2.5 py-1 text-slate-600 hover:bg-slate-100 font-bold" aria-label="Increase">+</button>
                                    </div>
                                </form>

                                <form method="post" action="<?= base_url('cart/remove') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                                    <button class="text-xs text-rose-600 hover:underline">Remove</button>
                                </form>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-black text-sm sm:text-base whitespace-nowrap"><?= kb_money((int) $it['line_total']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary aside -->
            <aside class="bg-white rounded-2xl p-4 sm:p-5 h-fit lg:sticky lg:top-24 space-y-3">
                <!-- Coupon entry -->
                <form method="post" action="<?= base_url('cart/apply-coupon') ?>" class="space-y-2">
                    <?= csrf_field() ?>
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-700">Have a coupon?</label>
                    <div class="flex gap-2">
                        <input type="text" name="code" placeholder="e.g. WELCOME10"
                               class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase tracking-wider focus:border-brand-400 focus:outline-none">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-brand-500 text-white text-sm font-bold transition shrink-0">Apply</button>
                    </div>
                </form>

                <!-- Applied promotions (with remove) -->
                <?php if (! empty($promotions)): ?>
                    <div class="space-y-1.5">
                        <?php foreach ($promotions as $ap): ?>
                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-emerald-600">✓</span>
                                    <span class="text-xs font-bold text-emerald-800 font-mono truncate"><?= esc($ap['coupon_code']) ?></span>
                                    <span class="text-xs text-emerald-700 truncate">− <?= kb_money((int) $ap['discount_amount']) ?></span>
                                </div>
                                <form method="post" action="<?= base_url('cart/remove-coupon') ?>" class="shrink-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="code" value="<?= esc($ap['coupon_code'], 'attr') ?>">
                                    <button class="text-xs text-rose-600 hover:underline">Remove</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="pt-3 border-t border-slate-100 space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-700"><span>Subtotal</span><span><?= kb_money($subtotal) ?></span></div>
                    <?php if ($discountTot > 0): ?>
                        <div class="flex justify-between text-emerald-700 font-semibold"><span>Discount</span><span>− <?= kb_money($discountTot) ?></span></div>
                    <?php endif; ?>
                    <div class="flex justify-between text-slate-700">
                        <span>Shipping</span>
                        <span><?= $shippingTot > 0 ? kb_money($shippingTot) : '<span class="text-emerald-700 font-bold">FREE</span>' ?></span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-200 flex justify-between items-baseline">
                    <span class="text-sm font-bold uppercase tracking-wide text-slate-700">Total</span>
                    <span class="text-xl sm:text-2xl font-black text-slate-900"><?= kb_money($grandTotal) ?></span>
                </div>
                <p class="text-[11px] text-slate-500">Inclusive of GST · Earn <?= number_format((int) floor($grandTotal / 100)) ?> Khoobie points on this order.</p>

                <a href="<?= base_url('checkout') ?>" class="block text-center btn-primary w-full">Checkout securely &rarr;</a>

                <!-- Trust strip -->
                <div class="grid grid-cols-2 gap-2 pt-2 text-[11px] text-slate-600">
                    <div class="flex items-center gap-1.5"><span class="text-emerald-500">✓</span> Secure checkout</div>
                    <div class="flex items-center gap-1.5"><span class="text-emerald-500">✓</span> COD available</div>
                    <div class="flex items-center gap-1.5"><span class="text-emerald-500">✓</span> 7-day returns</div>
                    <div class="flex items-center gap-1.5"><span class="text-emerald-500">✓</span> Securely packed</div>
                </div>
            </aside>
        </div>

        <!-- Cross-sell — "Frequently bought" -->
        <?php if (! empty($upsells)): ?>
            <div class="mt-8 sm:mt-10">
                <h2 class="text-base sm:text-lg font-black text-slate-900">You may also like</h2>
                <p class="text-xs text-slate-500">Add more to unlock bigger discounts ✨</p>
                <div class="mt-3 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <?php foreach ($upsells as $p): ?>
                        <?= view('App\Modules\Storefront\Views\_product_card', [
                            'p'            => $p,
                            'cartVariants' => $cartVariants ?? [],
                            'shortlistIds' => $shortlistIds ?? [],
                            'compareIds'   => $compareIds   ?? [],
                        ]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<!-- Recently viewed (session-tracked) -->
<?= view('partials/_recently_viewed', [
    'items'        => $recentlyViewed ?? [],
    'cartVariants' => $cartVariants ?? [],
    'bg'           => 'bg-white',
]) ?>

<!--
   Cart-page only: when an upsell card adds-to-cart or its stepper changes qty,
   the line-items list, summary totals, free-shipping nudge, applied offers AND
   the upsell slot all need to reflect the new cart. Easiest correct UX = refresh.
   Debounced so rapid +/- clicks coalesce into a single reload.
-->
<script>
(function () {
    let pending = null;
    function queueReload() {
        clearTimeout(pending);
        // 800ms lets the toast flash briefly + the green stepper appear before refresh
        pending = setTimeout(function () { location.reload(); }, 800);
    }
    window.addEventListener('cart:added',         queueReload);
    window.addEventListener('cart:item-updated',  queueReload);
})();
</script>

<?= $this->endSection() ?>
