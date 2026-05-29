<?php
/**
 * Recently-viewed strip. Reuses _product_card for visual consistency.
 *
 * Caller passes:
 *   $items         array of hydrated product rows from RecentlyViewedService::list()
 *   $heading       optional, defaults to "Recently viewed"
 *   $subheading    optional caption
 *   $bg            optional background class for the section ('bg-white' / 'bg-slate-50')
 *   $limit         optional — how many to render here (default 8); the full list lives at /recently-viewed
 *
 * Renders nothing if items are empty.
 */
$items      = $items      ?? [];
$heading    = $heading    ?? 'Recently viewed';
$subheading = $subheading ?? 'Pick up where you left off';
$bg         = $bg         ?? 'bg-white';
$limit      = $limit      ?? 8;

if (empty($items)) return;

$visible   = array_slice($items, 0, $limit);
$hasMore   = count($items) > $limit;
?>
<section class="py-8 sm:py-10 lg:py-12 <?= esc($bg, 'attr') ?>">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <div>
                <span class="text-xs uppercase tracking-wider font-bold text-slate-500">🕒 You browsed</span>
                <h2 class="mt-1 text-xl sm:text-2xl font-black"><?= esc($heading) ?></h2>
                <p class="text-xs sm:text-sm text-slate-500"><?= esc($subheading) ?></p>
            </div>
            <a href="<?= base_url('recently-viewed') ?>" class="text-xs sm:text-sm font-semibold text-brand-600 hover:underline whitespace-nowrap">
                <?= $hasMore ? 'View all →' : 'See history →' ?>
            </a>
        </div>
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php foreach ($visible as $p): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', [
                    'p'            => $p,
                    'cartVariants' => $cartVariants ?? [],
                    'shortlistIds' => $shortlistIds ?? [],
                    'compareIds'   => $compareIds   ?? [],
                ]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
