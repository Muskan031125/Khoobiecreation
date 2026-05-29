<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-6 sm:py-10 lg:py-14 bg-gradient-to-br from-amber-50 via-rose-50 to-violet-50">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 lg:px-6">

        <nav class="text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-3">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <a href="<?= base_url('shop') ?>" class="hover:underline">Shop</a> <span>&raquo;</span>
            <span class="text-slate-900 font-semibold">Bundle</span>
        </nav>

        <div class="grid lg:grid-cols-[1.2fr_1fr] gap-6 lg:gap-10 items-start">
            <div>
                <span class="eyebrow text-violet-700">📦 Bundle</span>
                <h1 class="h-display text-3xl sm:text-4xl lg:text-5xl mt-1 text-slate-900"><?= esc($bundle['name']) ?></h1>
                <?php if (! empty($bundle['tagline'])): ?>
                    <p class="mt-2 text-base text-slate-600"><?= esc($bundle['tagline']) ?></p>
                <?php endif; ?>

                <div class="mt-4 flex items-baseline gap-3 flex-wrap">
                    <span class="text-4xl font-display font-black text-brand-600">₹<?= number_format(round($bundle['bundle_price']/100)) ?></span>
                    <?php if ($bundle['items_total'] > $bundle['bundle_price']): ?>
                        <span class="text-xl text-slate-400 line-through">₹<?= number_format(round($bundle['items_total']/100)) ?></span>
                        <span class="px-3 py-1 rounded-full bg-emerald-600 text-white font-black text-sm">Save ₹<?= number_format(round($bundle['savings']/100)) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (! empty($bundle['description'])): ?>
                    <div class="mt-4 prose prose-slate max-w-none text-slate-700 leading-relaxed"><?= kb_safe_html($bundle['description']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('bundle/' . $bundle['slug'] . '/add') ?>" class="mt-6">
                    <?= csrf_field() ?>
                    <button type="submit" class="w-full sm:w-auto px-8 h-12 rounded-full bg-brand-500 hover:bg-brand-600 text-white font-bold uppercase tracking-wider shadow-cta hover:shadow-cta-lg hover:-translate-y-0.5 transition">
                        🛒 Add bundle to cart
                    </button>
                </form>
            </div>

            <?php if (! empty($bundle['hero_image'])): ?>
                <img src="<?= esc($bundle['hero_image']) ?>" alt="<?= esc($bundle['name']) ?>" class="rounded-3xl shadow-soft-lg aspect-square object-cover">
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-8 sm:py-10 bg-white">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 lg:px-6">
        <h2 class="font-display text-2xl font-black">What's inside</h2>
        <div class="mt-4 space-y-3">
            <?php foreach ($bundle['items'] as $it):
                $img = $it['hero_image'];
                if ($img && ! preg_match('#^https?://#', $img)) $img = base_url($img);
                $price = (int) ($it['price'] ?? 0);
            ?>
                <a href="<?= base_url('product/' . $it['slug']) ?>" class="flex gap-4 bg-slate-50 hover:bg-slate-100 rounded-2xl p-4 transition">
                    <img src="<?= esc($img) ?>" class="w-24 h-24 rounded-xl object-cover shrink-0" alt="">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 font-bold uppercase tracking-wider"><?= esc($it['role']) ?></span>
                            <span class="text-slate-500 capitalize"><?= esc($it['type']) ?></span>
                        </div>
                        <h3 class="mt-1 font-display font-black text-slate-900 line-clamp-2"><?= esc($it['name']) ?></h3>
                        <?php if ($it['short_desc']): ?>
                            <p class="mt-1 text-sm text-slate-600 line-clamp-2"><?= esc($it['short_desc']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-lg font-black text-slate-900 whitespace-nowrap">₹<?= number_format(round($price/100)) ?></div>
                        <?php if ((int) $it['qty'] > 1): ?><div class="text-xs text-slate-500">×<?= (int) $it['qty'] ?></div><?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 p-5 rounded-2xl bg-emerald-50 border-2 border-dashed border-emerald-300 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-emerald-700">Bundle saves you</div>
                <div class="text-2xl font-display font-black text-emerald-700">₹<?= number_format(round($bundle['savings']/100)) ?></div>
            </div>
            <form method="post" action="<?= base_url('bundle/' . $bundle['slug'] . '/add') ?>">
                <?= csrf_field() ?>
                <button class="btn-primary">Add bundle → Save more</button>
            </form>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
