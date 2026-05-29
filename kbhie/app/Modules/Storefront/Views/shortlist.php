<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-5 sm:py-8 lg:py-12 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-6xl px-3 sm:px-4 lg:px-6">

        <nav class="text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-2">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <span class="text-slate-900 font-semibold">Shortlist</span>
        </nav>
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-black flex items-center gap-2">
                    <svg class="w-6 h-6 text-rose-500" fill="currentColor" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    Your Shortlist
                    <?php if (! empty($items)): ?>
                        <span class="text-slate-400 font-normal text-base">· <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?></span>
                    <?php endif; ?>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Saved for later. Move them to cart when you're ready.</p>
            </div>
            <a href="<?= base_url('shop') ?>" class="text-xs sm:text-sm text-brand-600 font-semibold hover:underline">&larr; Continue shopping</a>
        </div>

        <?php if (empty($items)): ?>
            <div class="mt-8 bg-white rounded-2xl p-8 sm:p-10 text-center">
                <div class="text-5xl">🤍</div>
                <h2 class="mt-3 text-lg font-bold">Your shortlist is empty</h2>
                <p class="mt-1 text-slate-600">Tap the heart on any product to save it here.</p>
                <a href="<?= base_url('shop') ?>" class="mt-5 inline-block btn-primary">Browse products &rarr;</a>
            </div>
        <?php else: ?>

        <!-- Shortlist grid: each card is the standard product card.
             Below each card we render an extra "Buy Now" button so the user can convert without clicking through. -->
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php foreach ($items as $p): ?>
                <div class="space-y-2">
                    <?= view('App\Modules\Storefront\Views\_product_card', [
                        'p' => $p,
                        'cartVariants' => $cartVariants ?? [],
                        'shortlistIds' => $shortlistIds ?? [],
                        'compareIds'   => $compareIds ?? [],
                    ]) ?>
                    <!-- Convert-now Buy button shown below the card on this page only -->
                    <button type="button"
                            class="w-full h-10 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold uppercase tracking-wider shadow-cta transition"
                            data-shortlist-buy-now
                            data-variant-id="<?= (int) ($p['variant_id'] ?? 0) ?>"
                            data-product-name="<?= esc($p['name'], 'attr') ?>"
                            data-product-image="<?= esc($p['hero_image'] ?? '', 'attr') ?>">
                        Buy Now &rarr;
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- "Buy Now" inline handler — adds the variant then redirects to cart -->
        <script>
        document.querySelectorAll('[data-shortlist-buy-now]').forEach(btn => {
            btn.addEventListener('click', async () => {
                btn.disabled = true; const old = btn.textContent; btn.textContent = 'Adding…';
                const j = await window.kbCart.add(
                    parseInt(btn.dataset.variantId, 10), 1,
                    { productName: btn.dataset.productName || '', productImage: btn.dataset.productImage || '' }
                );
                if (j.ok) { location.href = '<?= base_url('cart') ?>'; }
                else { btn.disabled = false; btn.textContent = old; }
            });
        });
        </script>

        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
