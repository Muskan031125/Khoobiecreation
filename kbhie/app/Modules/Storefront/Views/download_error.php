<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-20 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-md px-4 text-center">
        <div class="text-6xl">😕</div>
        <h1 class="h-display text-3xl mt-4 text-slate-900">Download unavailable</h1>
        <p class="mt-2 text-slate-600"><?= esc($error) ?></p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="<?= base_url('account/downloads') ?>" class="btn-primary">My downloads</a>
            <a href="<?= base_url('shop') ?>" class="btn-ghost">Back to shop</a>
        </div>
        <p class="mt-6 text-xs text-slate-500">Need help? Email <a href="mailto:<?= esc($brand['email']) ?>" class="text-brand-600 hover:underline"><?= esc($brand['email']) ?></a></p>
    </div>
</section>

<?= $this->endSection() ?>
