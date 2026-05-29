<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="relative overflow-hidden bg-gradient-to-br from-amber-50 via-rose-50 to-violet-50 py-10 sm:py-16">
    <div class="relative mx-auto max-w-5xl px-3 sm:px-4 lg:px-6 text-center">
        <span class="eyebrow text-amber-700">📍 In-person</span>
        <h1 class="h-display text-3xl sm:text-5xl lg:text-6xl mt-2 text-slate-900">Real workshops, real friends</h1>
        <p class="mt-3 text-base sm:text-lg text-slate-700 max-w-2xl mx-auto">Weekend pottery sessions, painting clubs, swim coaching, karate dojos — find what's happening near you.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <?php foreach (array_slice($cities, 0, 8) as $c): ?>
                <a href="<?= base_url('shop/local-meetups?city=' . urlencode($c['city'])) ?>" class="px-4 py-2 rounded-full bg-white hover:bg-slate-50 text-slate-900 font-bold text-sm shadow-sm">
                    📍 <?= esc($c['city']) ?> <span class="text-slate-400">(<?= $c['n'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-8 sm:py-12 bg-white">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <h2 class="h-display text-2xl sm:text-3xl">Upcoming workshops</h2>
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php foreach ($upcoming as $p): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p, 'cartVariants'=>$cartVariants??[], 'shortlistIds'=>$shortlistIds??[], 'compareIds'=>$compareIds??[]]) ?>
            <?php endforeach; ?>
        </div>
        <?php if (empty($upcoming)): ?>
            <p class="mt-10 text-center text-slate-500">Nothing scheduled in your area right now — check back soon or follow us on Insta for updates.</p>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
