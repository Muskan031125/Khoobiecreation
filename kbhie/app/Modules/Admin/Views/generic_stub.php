<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="bg-white rounded-2xl shadow-sm p-10 text-center">
    <h1 class="text-2xl font-black"><?= esc($title) ?></h1>
    <p class="mt-2 text-slate-500">Edit form for this resource will be added in a follow-up. For now you can view the listing.</p>
    <a href="javascript:history.back()" class="mt-5 inline-block text-brand-600 font-semibold hover:underline">&larr; Back</a>
</div>

<?= $this->endSection() ?>
