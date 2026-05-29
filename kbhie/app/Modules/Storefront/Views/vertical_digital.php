<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="relative overflow-hidden bg-gradient-to-br from-emerald-50 via-sky-50 to-violet-50 py-10 sm:py-16">
    <div class="relative mx-auto max-w-5xl px-3 sm:px-4 lg:px-6 text-center">
        <span class="eyebrow text-emerald-700">💾 Instant access</span>
        <h1 class="h-display text-3xl sm:text-5xl lg:text-6xl mt-2 text-slate-900">Download. Print. Play.</h1>
        <p class="mt-3 text-base sm:text-lg text-slate-700 max-w-2xl mx-auto">Printable worksheets, activity packs, recipe cards, colouring sheets — get them now, no shipping wait.</p>
        <div class="mt-6 inline-flex items-center gap-3 px-4 py-2 rounded-full bg-white shadow-sm text-xs font-bold">
            <span>⚡ Instant download</span><span>·</span><span>♾️ Lifetime access</span><span>·</span><span>📱 Print or use on tablet</span>
        </div>
    </div>
</section>

<section class="py-8 sm:py-12 bg-white">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <?php if (empty($digitals)): ?>
            <div class="text-center py-16">
                <p class="text-slate-500">Digital catalog launching soon. Want to be the first to know?</p>
                <a href="<?= base_url('shop') ?>" class="mt-4 inline-block btn-primary">Browse physical kits meanwhile →</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                <?php foreach ($digitals as $p): ?>
                    <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p, 'cartVariants'=>$cartVariants??[], 'shortlistIds'=>$shortlistIds??[], 'compareIds'=>$compareIds??[]]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
