<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php
// Family-aware page chrome
$familyLabels = [
    'meetups'  => ['eyebrow' => '📍 In-person',    'tag' => 'Local meetups'],
    'classes'  => ['eyebrow' => '🎓 Live & on-demand', 'tag' => 'Classes & coaching'],
    'physical' => ['eyebrow' => '🎁 Hands-on kits', 'tag' => 'Products'],
    'digital'  => ['eyebrow' => '💾 Instant access','tag' => 'Digital'],
    'all'      => ['eyebrow' => '✨ Everything',    'tag' => 'All products'],
];
$cfg = $familyLabels[$filter_family ?? 'all'] ?? $familyLabels['all'];
?>

<section class="bg-gradient-to-b from-slate-50 to-white py-6 sm:py-8 lg:py-10 border-b border-slate-100">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <nav class="text-[11px] sm:text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-2">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <a href="<?= base_url('shop') ?>" class="hover:underline">Shop</a>
            <?php if ($category): ?>
                <span>&raquo;</span>
                <span class="text-slate-900 font-semibold"><?= esc($category['name']) ?></span>
            <?php endif; ?>
        </nav>
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <div>
                <span class="eyebrow text-brand-600"><?= esc($cfg['eyebrow']) ?></span>
                <h1 class="h-display text-2xl sm:text-3xl lg:text-4xl mt-1 text-slate-900">
                    <?= esc($category['name'] ?? 'Shop everything') ?>
                </h1>
                <?php if (! empty($category['description'])): ?>
                    <p class="mt-1.5 text-sm text-slate-600 max-w-2xl"><?= esc($category['description']) ?></p>
                <?php endif; ?>
            </div>
            <p class="text-xs text-slate-500"><?= count($products) ?> result<?= count($products) === 1 ? '' : 's' ?></p>
        </div>
    </div>
</section>

<section class="py-5 sm:py-8 bg-slate-50">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <div class="grid lg:grid-cols-12 gap-4 lg:gap-6">

            <!-- Adaptive filters -->
            <?= view('partials/_catalog_filters', [
                'filter_family' => $filter_family ?? 'all',
                'facets'        => $facets ?? [],
                'filters'       => $filters,
                'category'      => $category,
            ]) ?>

            <!-- Listing -->
            <div class="lg:col-span-9">
                <!-- Sort bar + category pills row -->
                <div class="bg-white rounded-xl border border-slate-200 p-3 mb-4 flex items-center justify-between gap-3 flex-wrap">
                    <!-- Sub-category pills (sibling categories) -->
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar flex-1 min-w-0">
                        <?php
                        $shownCats = $category
                            ? array_filter($categories, fn ($c) => $c['parent_id'] == ($category['parent_id'] ?? null))
                            : array_filter($categories, fn ($c) => empty($c['parent_id']));
                        foreach ($shownCats as $c): $active = isset($category['id']) && $category['id'] == $c['id']; ?>
                            <a href="<?= base_url('shop/' . $c['slug']) ?>"
                               class="shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition <?= $active ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                                <?= esc($c['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <form method="get" class="text-sm shrink-0">
                        <?php foreach (array_filter($filters ?? [], fn ($v, $k) => $v && ! in_array($k, ['sort','page','per_page','filter_family']), ARRAY_FILTER_USE_BOTH) as $k => $v): ?>
                            <input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>">
                        <?php endforeach; ?>
                        <label class="text-slate-500 mr-2 hidden sm:inline">Sort by</label>
                        <select name="sort" onchange="this.form.submit()" class="px-2 py-1.5 border-2 border-slate-200 rounded-lg text-sm font-semibold focus:border-brand-400 focus:outline-none">
                            <option value="featured"    <?= ($filters['sort'] ?? '') === 'featured'    ? 'selected' : '' ?>>Featured</option>
                            <option value="newest"      <?= ($filters['sort'] ?? '') === 'newest'      ? 'selected' : '' ?>>Newest first</option>
                            <option value="price_asc"   <?= ($filters['sort'] ?? '') === 'price_asc'   ? 'selected' : '' ?>>Price · low → high</option>
                            <option value="price_desc"  <?= ($filters['sort'] ?? '') === 'price_desc'  ? 'selected' : '' ?>>Price · high → low</option>
                            <option value="rating"      <?= ($filters['sort'] ?? '') === 'rating'      ? 'selected' : '' ?>>Top rated</option>
                            <option value="bestselling" <?= ($filters['sort'] ?? '') === 'bestselling' ? 'selected' : '' ?>>Bestselling</option>
                        </select>
                    </form>
                </div>

                <?php if (empty($products)): ?>
                    <div class="text-center py-16 bg-white rounded-2xl border border-slate-200">
                        <div class="text-5xl mb-2">🔍</div>
                        <h2 class="font-display text-xl font-black text-slate-900">No matches for these filters</h2>
                        <p class="mt-1 text-sm text-slate-500">Try widening the filters or browsing the full catalog.</p>
                        <a href="<?= base_url($category ? 'shop/' . $category['slug'] : 'shop') ?>" class="mt-4 inline-block btn-primary">Clear filters &rarr;</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                        <?php foreach ($products as $p): ?>
                            <?= view('App\Modules\Storefront\Views\_product_card', [
                                'p' => $p,
                                'cartVariants' => $cartVariants ?? [],
                                'shortlistIds' => $shortlistIds ?? [],
                                'compareIds'   => $compareIds ?? [],
                            ]) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
