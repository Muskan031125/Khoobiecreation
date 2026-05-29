<!-- Sticky bottom CTA bar — mobile only. Visibility toggled per-page via Alpine. -->
<div class="fixed bottom-0 inset-x-0 z-50 lg:hidden bg-white border-t border-slate-200 shadow-lg" x-data="{ show: true }" x-show="show" style="display:none">
    <div class="grid grid-cols-2">
        <a href="<?= base_url('shop') ?>" class="flex items-center justify-center gap-2 py-3 text-sm font-semibold text-slate-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h11l3-8H6.4M7 13l-1.7 5h13.4"/></svg>
            Shop
        </a>
        <a href="<?= base_url('cart') ?>" class="flex items-center justify-center gap-2 py-3 text-sm font-bold bg-rose-500 text-white">
            <?php if (!empty($cart_count)): ?>
                View cart (<?= esc($cart_count) ?>)
            <?php else: ?>
                Buy Now &rarr;
            <?php endif; ?>
        </a>
    </div>
</div>
