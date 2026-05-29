<?php
/**
 * PDP Bundles block — "save when you grab both" upsell, the kit↔class flywheel made real.
 * Renders only if current product belongs to at least one active bundle.
 */
$bundles = (new \App\Libraries\BundleService())->forProduct((int) $product['id']);
if (empty($bundles)) return;
?>
<section class="py-8 sm:py-10 bg-gradient-to-br from-amber-50 via-rose-50 to-violet-50">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <span class="eyebrow text-violet-700">📦 Better together</span>
        <h2 class="h-display text-xl sm:text-2xl mt-1 text-slate-900">Save when you grab both</h2>

        <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <?php foreach ($bundles as $b): ?>
                <a href="<?= base_url('bundle/' . $b['slug']) ?>" class="group block bg-white rounded-2xl ring-1 ring-slate-200 hover:ring-violet-300 hover:shadow-soft-lg overflow-hidden transition">
                    <?php if (! empty($b['hero_image'])): ?>
                        <div class="aspect-[16/9] bg-slate-100 overflow-hidden">
                            <img src="<?= esc($b['hero_image']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="">
                        </div>
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-display font-black text-base text-slate-900 line-clamp-2"><?= esc($b['name']) ?></h3>
                        <?php if (! empty($b['tagline'])): ?>
                            <p class="mt-1 text-xs text-slate-600 line-clamp-2"><?= esc($b['tagline']) ?></p>
                        <?php endif; ?>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-lg font-black text-slate-900">₹<?= number_format(round($b['bundle_price']/100)) ?></span>
                            <?php if ($b['items_total'] > $b['bundle_price']): ?>
                                <span class="text-xs text-slate-400 line-through">₹<?= number_format(round($b['items_total']/100)) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($b['savings'] > 0): ?>
                            <div class="mt-1 inline-block text-[10px] font-black uppercase tracking-wider bg-emerald-600 text-white px-2 py-0.5 rounded">
                                Save ₹<?= number_format(round($b['savings']/100)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
