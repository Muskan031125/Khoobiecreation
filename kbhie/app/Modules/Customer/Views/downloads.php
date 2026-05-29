<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-6 sm:py-10 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 lg:px-6">

        <?= view('App\Modules\Customer\Views\_account_nav') ?>

        <span class="eyebrow text-violet-600">💾 Instant access</span>
        <h1 class="h-display text-2xl sm:text-3xl mt-1 text-slate-900">My Downloads</h1>
        <p class="text-sm text-slate-500 mt-1">Every digital file you've purchased. Each link works for 10 downloads over 90 days.</p>

        <?php if (empty($rows)): ?>
            <div class="mt-6 bg-white rounded-2xl p-8 text-center">
                <div class="text-5xl">💾</div>
                <h2 class="mt-3 font-display font-black text-lg">No downloads yet</h2>
                <p class="mt-1 text-sm text-slate-500">Buy a digital product and the download links will appear here.</p>
                <a href="<?= base_url('digital') ?>" class="mt-4 inline-block btn-primary">Browse digital products →</a>
            </div>
        <?php else: ?>
            <div class="mt-5 space-y-3">
                <?php foreach ($rows as $r): ?>
                    <div class="bg-white rounded-2xl p-4 shadow-soft flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-900"><?= esc($r['product_name']) ?></div>
                            <div class="text-xs text-slate-500 font-mono mt-0.5"><?= esc($r['file_name']) ?></div>
                            <div class="text-[11px] text-slate-500 mt-1">
                                Downloaded <?= (int) $r['downloads_count'] ?> / <?= (int) $r['max_downloads'] ?> times
                                <?php if (! empty($r['expires_at'])): ?>
                                    · Expires <?= date('j M Y', strtotime($r['expires_at'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="<?= base_url('download/' . $r['token']) ?>"
                           class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold transition shrink-0">
                            ⬇ Download
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
