<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-8 lg:py-12 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4">
        <div class="grid lg:grid-cols-[260px_1fr] gap-6">
            <?= $this->include('App\Modules\Customer\Views\_account_nav') ?>
            <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                <h1 class="text-2xl font-black"><?= esc($heading) ?></h1>
                <p class="mt-2 text-slate-500 text-sm">Coming soon — this area is being built.</p>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
