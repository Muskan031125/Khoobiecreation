<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php
// Build the union set of attribute names across all selected products,
// so the comparison rows include every attribute any of them has.
$attrNames = [];
foreach ($items as $it) {
    foreach ($it['attributes'] ?? [] as $a) {
        $attrNames[$a['name']] = true;
    }
}
$attrNames = array_keys($attrNames);
$typeLabels = [
    'simple'    => 'Physical kit',
    'variable'  => 'Physical kit',
    'bundle'    => 'Bundle',
    'digital'   => 'Digital download',
    'course'    => 'Online course',
    'tuition'   => 'Live online class',
    'meetup'    => 'In-person meetup',
    'service'   => '1-on-1 service',
    'affiliate' => 'Partner product',
    'membership'=> 'Membership',
];
?>

<section class="py-5 sm:py-8 lg:py-10 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">

        <nav class="text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-2">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <span class="text-slate-900 font-semibold">Compare</span>
        </nav>
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-black flex items-center gap-2">
                    <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18M9 7v14M15 12v9M21 5v16"/></svg>
                    Compare Products
                    <span class="text-slate-400 font-normal text-base">· <?= count($items) ?>/<?= $max ?></span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Up to <?= $max ?> products side-by-side. Drop any to swap in another.</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (! empty($items)): ?>
                    <form method="post" action="<?= base_url('compare/clear') ?>">
                        <?= csrf_field() ?>
                        <button class="text-xs text-rose-600 font-semibold hover:underline">Clear all</button>
                    </form>
                <?php endif; ?>
                <a href="<?= base_url('shop') ?>" class="text-xs sm:text-sm text-brand-600 font-semibold hover:underline">&larr; Add more from shop</a>
            </div>
        </div>

        <?php if (empty($items)): ?>
            <div class="mt-8 bg-white rounded-2xl p-8 sm:p-10 text-center">
                <div class="text-5xl">⚖️</div>
                <h2 class="mt-3 text-lg font-bold">Nothing to compare yet</h2>
                <p class="mt-1 text-slate-600">Tap the compare icon on up to <?= $max ?> products to see them side-by-side here.</p>
                <a href="<?= base_url('shop') ?>" class="mt-5 inline-block btn-primary">Pick products to compare &rarr;</a>
            </div>

        <?php else: ?>

        <!-- Side-by-side comparison: column-per-product, scrollable horizontally on mobile -->
        <div class="mt-5 overflow-x-auto -mx-3 px-3">
            <table class="w-full min-w-[640px] bg-white rounded-2xl shadow-sm border-separate border-spacing-0">

                <!-- Header row: thumbs + names + remove buttons -->
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-white w-32 sm:w-44 align-top text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100"></th>
                        <?php foreach ($items as $p):
                            $hero = $p['hero_image'] ?? null;
                            $heroSrc = $hero ? (preg_match('#^https?://#', $hero) ? $hero : base_url($hero)) : null;
                            $price   = (int) ($p['price'] ?? 0);
                            $compare = (int) ($p['compare_at_price'] ?? 0);
                            $discount = ($compare > $price && $price > 0) ? (int) round((1 - $price / $compare) * 100) : 0;
                        ?>
                            <th class="align-top p-3 sm:p-4 border-b border-slate-100 min-w-[200px]">
                                <div class="relative">
                                    <a href="<?= base_url('product/' . $p['slug']) ?>" class="block aspect-square rounded-lg overflow-hidden bg-slate-100">
                                        <?php if ($heroSrc): ?>
                                            <img src="<?= esc($heroSrc, 'attr') ?>" alt="<?= esc($p['name'], 'attr') ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-3xl">🎁</div>
                                        <?php endif; ?>
                                    </a>
                                    <?php if ($discount > 0): ?>
                                        <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-brand-500 text-white text-[10px] font-black shadow">−<?= $discount ?>%</span>
                                    <?php endif; ?>
                                    <form method="post" action="<?= base_url('compare/remove') ?>" class="absolute top-1.5 right-1.5">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" title="Remove from compare"
                                                class="w-7 h-7 rounded-full bg-white/95 hover:bg-rose-500 hover:text-white text-slate-500 shadow ring-1 ring-slate-200 inline-flex items-center justify-center transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                </div>
                                <a href="<?= base_url('product/' . $p['slug']) ?>" class="mt-2.5 block font-bold text-sm leading-snug text-slate-900 hover:text-brand-600 line-clamp-2 normal-case">
                                    <?= esc($p['name']) ?>
                                </a>
                            </th>
                        <?php endforeach; ?>

                        <?php // Empty slots for "add another" hint
                        for ($i = count($items); $i < $max; $i++): ?>
                            <th class="align-top p-3 sm:p-4 border-b border-slate-100 min-w-[200px] hidden lg:table-cell">
                                <a href="<?= base_url('shop') ?>" class="block aspect-square rounded-lg border-2 border-dashed border-slate-200 hover:border-brand-400 hover:bg-brand-50 text-slate-400 hover:text-brand-600 flex items-center justify-center text-center text-xs font-semibold transition">
                                    + Add another
                                </a>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>

                <tbody class="text-sm">
                    <!-- Price -->
                    <tr>
                        <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100">Price</th>
                        <?php foreach ($items as $p):
                            $price   = (int) ($p['price'] ?? 0);
                            $compare = (int) ($p['compare_at_price'] ?? 0);
                        ?>
                            <td class="p-3 sm:p-4 border-b border-slate-100 align-top">
                                <div class="text-base font-black text-slate-900"><?= kb_money_short($price) ?></div>
                                <?php if ($compare > $price): ?>
                                    <div class="text-xs">
                                        <span class="text-slate-400 line-through"><?= kb_money_short($compare) ?></span>
                                        <span class="ml-1 text-emerald-700 font-bold">Save <?= kb_money_short($compare - $price) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Rating -->
                    <tr>
                        <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100">Rating</th>
                        <?php foreach ($items as $p):
                            $rating = (float) ($p['rating_avg'] ?? 0);
                            $cnt    = (int)   ($p['rating_count'] ?? 0);
                        ?>
                            <td class="p-3 sm:p-4 border-b border-slate-100 align-top">
                                <?php if ($rating > 0): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-600 text-white text-xs font-bold">
                                        <?= number_format($rating, 1) ?> <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    </span>
                                    <span class="ml-1 text-xs text-slate-500"><?= $cnt ?> reviews</span>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs">No reviews yet</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Type -->
                    <tr>
                        <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100">Type</th>
                        <?php foreach ($items as $p): ?>
                            <td class="p-3 sm:p-4 border-b border-slate-100 align-top text-slate-700">
                                <?= esc($typeLabels[$p['type']] ?? ucfirst($p['type'])) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Category -->
                    <tr>
                        <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100">Category</th>
                        <?php foreach ($items as $p): ?>
                            <td class="p-3 sm:p-4 border-b border-slate-100 align-top text-slate-700">
                                <?= esc($p['category']['name'] ?? '—') ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Age range -->
                    <tr>
                        <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100">Age range</th>
                        <?php foreach ($items as $p):
                            $min = (int) ($p['age_min_years'] ?? 0);
                            $max_age = (int) ($p['age_max_years'] ?? 0);
                        ?>
                            <td class="p-3 sm:p-4 border-b border-slate-100 align-top text-slate-700">
                                <?php if ($min || $max_age): ?>
                                    <?= $min ?>–<?= $max_age ?> yrs
                                <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Stock -->
                    <tr>
                        <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100">Stock</th>
                        <?php foreach ($items as $p):
                            $stock = (int) ($p['total_stock'] ?? 0);
                        ?>
                            <td class="p-3 sm:p-4 border-b border-slate-100 align-top">
                                <?php if ($stock <= 0): ?>
                                    <span class="text-rose-600 text-xs font-bold">Out of stock</span>
                                <?php elseif ($stock <= 5): ?>
                                    <span class="text-amber-600 text-xs font-bold">Only <?= $stock ?> left</span>
                                <?php else: ?>
                                    <span class="text-emerald-700 text-xs font-bold">In stock</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Short description -->
                    <tr>
                        <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100">About</th>
                        <?php foreach ($items as $p): ?>
                            <td class="p-3 sm:p-4 border-b border-slate-100 align-top text-xs text-slate-600 leading-relaxed">
                                <?= esc(character_limiter($p['short_desc'] ?? '', 120)) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Attribute rows (union of attribute names across all products) -->
                    <?php foreach ($attrNames as $attrName): ?>
                        <tr>
                            <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 border-b border-slate-100"><?= esc($attrName) ?></th>
                            <?php foreach ($items as $p):
                                $val = '—';
                                foreach ($p['attributes'] ?? [] as $a) {
                                    if ($a['name'] === $attrName) { $val = $a['value']; break; }
                                }
                            ?>
                                <td class="p-3 sm:p-4 border-b border-slate-100 align-top text-slate-700 text-xs"><?= esc($val) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Actions: Add to Cart + Buy Now per column -->
                    <tr>
                        <th class="sticky left-0 z-10 bg-white text-left text-xs uppercase tracking-wide font-bold text-slate-500 p-3 sm:p-4 align-top">Actions</th>
                        <?php foreach ($items as $p):
                            $vid = (int) ($p['variant_id'] ?? 0);
                            $heroSrc = $p['hero_image'] ?? '';
                            if ($heroSrc && ! preg_match('#^https?://#', $heroSrc)) $heroSrc = base_url($heroSrc);
                        ?>
                            <td class="p-3 sm:p-4 align-top">
                                <div class="space-y-2">
                                    <button type="button"
                                            class="w-full h-10 rounded-lg bg-slate-900 hover:bg-brand-500 text-white text-xs font-bold uppercase tracking-wider transition disabled:opacity-50"
                                            data-compare-add
                                            data-variant-id="<?= $vid ?>"
                                            data-product-name="<?= esc($p['name'], 'attr') ?>"
                                            data-product-image="<?= esc($heroSrc, 'attr') ?>">
                                        Add to cart
                                    </button>
                                    <button type="button"
                                            class="w-full h-10 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold uppercase tracking-wider shadow-cta transition disabled:opacity-50"
                                            data-compare-buy-now
                                            data-variant-id="<?= $vid ?>"
                                            data-product-name="<?= esc($p['name'], 'attr') ?>"
                                            data-product-image="<?= esc($heroSrc, 'attr') ?>">
                                        Buy Now &rarr;
                                    </button>
                                    <a href="<?= base_url('product/' . $p['slug']) ?>"
                                       class="block text-center text-xs text-slate-500 hover:text-brand-600 hover:underline">
                                        View details
                                    </a>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <script>
        (function () {
            function attach(selector, after) {
                document.querySelectorAll(selector).forEach(btn => {
                    btn.addEventListener('click', async () => {
                        btn.disabled = true; const old = btn.innerHTML; btn.textContent = 'Adding…';
                        const j = await window.kbCart.add(
                            parseInt(btn.dataset.variantId, 10), 1,
                            { productName: btn.dataset.productName || '', productImage: btn.dataset.productImage || '' }
                        );
                        if (j.ok) after(); else { btn.disabled = false; btn.innerHTML = old; }
                    });
                });
            }
            attach('[data-compare-add]', () => location.reload());
            attach('[data-compare-buy-now]', () => location.href = '<?= base_url('cart') ?>');
        })();
        </script>

        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
