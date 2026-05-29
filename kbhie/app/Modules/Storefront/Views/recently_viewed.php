<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-5 sm:py-8 lg:py-12 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-6xl px-3 sm:px-4 lg:px-6">

        <nav class="text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-2">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <span class="text-slate-900 font-semibold">Recently Viewed</span>
        </nav>
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-black flex items-center gap-2">
                    🕒 Recently Viewed
                    <?php if (! empty($items)): ?>
                        <span class="text-slate-400 font-normal text-base">· <?= count($items) ?> product<?= count($items) === 1 ? '' : 's' ?></span>
                    <?php endif; ?>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Your last <?= count($items) ?> visits, most recent first.</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (! empty($items)): ?>
                    <form method="post" action="<?= base_url('recently-viewed/clear') ?>">
                        <?= csrf_field() ?>
                        <button class="text-xs text-rose-600 font-semibold hover:underline">Clear history</button>
                    </form>
                <?php endif; ?>
                <a href="<?= base_url('shop') ?>" class="text-xs sm:text-sm text-brand-600 font-semibold hover:underline">&larr; Continue shopping</a>
            </div>
        </div>

        <?php if (empty($items)): ?>
            <div class="mt-8 bg-white rounded-2xl p-8 sm:p-10 text-center">
                <div class="text-5xl">🕒</div>
                <h2 class="mt-3 text-lg font-bold">No browsing history yet</h2>
                <p class="mt-1 text-slate-600">Products you view will appear here so you can pick up where you left off.</p>
                <a href="<?= base_url('shop') ?>" class="mt-5 inline-block btn-primary">Browse products &rarr;</a>
            </div>
        <?php else: ?>
            <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                <?php foreach ($items as $p): ?>
                    <?= view('App\Modules\Storefront\Views\_product_card', [
                        'p'            => $p,
                        'cartVariants' => $cartVariants ?? [],
                        'shortlistIds' => $shortlistIds ?? [],
                        'compareIds'   => $compareIds ?? [],
                    ]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
