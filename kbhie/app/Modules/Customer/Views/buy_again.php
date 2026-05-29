<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-6 sm:py-10 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-6xl px-3 sm:px-4 lg:px-6">

        <?= view('App\Modules\Customer\Views\_account_nav') ?>

        <span class="eyebrow text-emerald-600">🔁 Reorder</span>
        <h1 class="h-display text-2xl sm:text-3xl mt-1 text-slate-900">Buy Again</h1>
        <p class="text-sm text-slate-500 mt-1">Stuff you've bought before — one tap to add to cart.</p>

        <?php if (empty($rows)): ?>
            <div class="mt-6 bg-white rounded-2xl p-8 text-center">
                <div class="text-5xl">🛒</div>
                <h2 class="mt-3 font-display font-black text-lg">No past purchases yet</h2>
                <a href="<?= base_url('shop') ?>" class="mt-4 inline-block btn-primary">Browse shop →</a>
            </div>
        <?php else: ?>
            <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                <?php foreach ($rows as $p): ?>
                    <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p, 'cartVariants'=>$cartVariants??[], 'shortlistIds'=>$shortlistIds??[], 'compareIds'=>$compareIds??[]]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
