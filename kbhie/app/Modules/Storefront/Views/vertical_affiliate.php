<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="relative overflow-hidden bg-gradient-to-br from-rose-50 via-amber-50 to-emerald-50 py-10 sm:py-16">
    <div class="relative mx-auto max-w-5xl px-3 sm:px-4 lg:px-6 text-center">
        <span class="eyebrow text-rose-700">⭐ Editor-picked</span>
        <h1 class="h-display text-3xl sm:text-5xl lg:text-6xl mt-2 text-slate-900">The best of the rest</h1>
        <p class="mt-3 text-base sm:text-lg text-slate-700 max-w-2xl mx-auto">When something we love is sold by someone else, we link to it honestly. We earn a small commission — never at extra cost to you.</p>
    </div>
</section>

<section class="py-8 sm:py-12 bg-white">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php foreach ($picks as $p): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p, 'cartVariants'=>$cartVariants??[], 'shortlistIds'=>$shortlistIds??[], 'compareIds'=>$compareIds??[]]) ?>
            <?php endforeach; ?>
        </div>
        <p class="mt-8 text-center text-xs text-slate-500 max-w-2xl mx-auto">
            🔍 <strong>Our affiliate promise:</strong> If we include a product here, our editorial team has personally vetted it. We don't accept paid placements — only commissions from purchases made via our links.
        </p>
    </div>
</section>

<?= $this->endSection() ?>
