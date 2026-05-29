<?php
// Site-wide floating cart bar — appears on every public page (NOT PDP, cart, checkout, admin, partner, account, auth).
// Shows ONLY when cart has items, so empty visitors don't see a permanent footer block.
//
// PDP has its own richer pdp-aware sticky bar (`_pdp_sticky_cart.php`) — master.php detects PDP and skips this one.
//
// Listens to `cart:added` / `cart:item-updated` events to update count + subtotal live without page reload.

$initCount  = (int) ($cart_count ?? 0);
$initTotal  = 0;
$cartObj    = null;

if ($initCount > 0) {
    // Pull the live grand_total so the bar shows the right amount on first paint.
    try {
        $cartObj = (new \App\Libraries\Cart\CartService())->getCurrentCart();
        $initTotal = (int) ($cartObj['grand_total'] ?? 0);
    } catch (\Throwable $e) {
        $initTotal = 0;
    }
}

// Free-shipping threshold (in paise) — pulled from settings, default ₹999
$freeShipPaise = (int) ((new \App\Libraries\Cart\CartService())->setting('shipping', 'free_shipping_threshold', 99900));
?>
<div id="kb-floating-cart"
     x-data="kbFloatingCart({
         count: <?= $initCount ?>,
         total: <?= $initTotal ?>,
         freeShip: <?= $freeShipPaise ?>,
     })"
     x-show="count > 0 && !dismissed"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     @cart:added.window="onCartChange($event.detail)"
     @cart:item-updated.window="onItemUpdated($event.detail)"
     class="fixed bottom-0 inset-x-0 z-40 pointer-events-none"
     style="padding-bottom: env(safe-area-inset-bottom, 0px);">

    <div class="pointer-events-auto mx-auto max-w-3xl mb-2 sm:mb-3 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-[0_-4px_24px_rgba(0,0,0,0.12)] ring-1 ring-slate-200 overflow-hidden">

            <!-- Free-shipping progress strip (top) -->
            <div class="px-3 sm:px-4 pt-2.5 pb-1.5" x-show="freeShip > 0">
                <div class="flex items-center justify-between text-[11px] sm:text-xs font-semibold">
                    <span x-show="total < freeShip" class="text-slate-700">
                        Add <span class="text-brand-600 font-black" x-text="formatRupees(freeShip - total)"></span> more for
                        <span class="text-emerald-700">FREE shipping 🚚</span>
                    </span>
                    <span x-show="total >= freeShip" class="text-emerald-700 font-bold">
                        🎉 You've unlocked FREE shipping!
                    </span>
                    <button type="button" @click="dismissed = true"
                            class="text-slate-400 hover:text-slate-700 text-base leading-none ml-2 shrink-0"
                            aria-label="Hide cart bar">&times;</button>
                </div>
                <div class="mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all duration-500"
                         :style="'width: ' + Math.min(100, Math.round((total / freeShip) * 100)) + '%'"></div>
                </div>
            </div>

            <!-- Main row -->
            <div class="px-3 sm:px-4 pb-3 pt-2 flex items-center gap-2 sm:gap-3">
                <!-- Cart icon + count -->
                <a href="<?= base_url('cart') ?>" class="relative shrink-0 p-2 rounded-full bg-slate-100 hover:bg-slate-200 transition" aria-label="View cart">
                    <svg class="w-5 h-5 text-slate-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h11l3-8H6.4M7 13l-1.7 5h13.4M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm10 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/></svg>
                    <span data-cart-count
                          class="absolute -top-1 -right-1 bg-brand-500 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center"
                          x-text="count"><?= $initCount ?></span>
                </a>

                <!-- Subtotal -->
                <div class="flex-1 min-w-0">
                    <div class="text-[10px] sm:text-xs text-slate-500 uppercase tracking-wide font-semibold leading-tight">
                        <span x-text="count + (count === 1 ? ' item' : ' items')"></span> in cart
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 leading-tight truncate"
                         x-text="formatRupees(total)"></div>
                </div>

                <!-- Actions -->
                <a href="<?= base_url('cart') ?>"
                   class="hidden sm:inline-flex items-center px-3 py-2 rounded-lg border-2 border-slate-200 hover:border-brand-400 hover:bg-brand-50 text-slate-900 font-bold text-xs shrink-0 transition">
                    View Cart
                </a>
                <a href="<?= base_url('checkout') ?>"
                   class="inline-flex items-center justify-center px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs sm:text-sm shadow-cta shrink-0 whitespace-nowrap">
                    Checkout <span class="ml-1">&rarr;</span>
                </a>
            </div>
        </div>
    </div>
</div>
