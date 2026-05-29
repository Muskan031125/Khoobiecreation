<?= $this->extend('layouts/partner') ?>
<?= $this->section('content') ?>

<h1 class="text-xl font-black">My Profile</h1>
<div class="mt-4 bg-white rounded-2xl shadow-sm p-5 text-sm">
    <?php if ($partner): ?>
        <div class="grid sm:grid-cols-2 gap-3">
            <div><div class="text-xs text-slate-500">Company</div><div class="font-semibold"><?= esc($partner['company_name']) ?></div></div>
            <div><div class="text-xs text-slate-500">GSTIN</div><div><?= esc($partner['gstin'] ?: '—') ?></div></div>
            <div><div class="text-xs text-slate-500">Contact</div><div><?= esc($partner['contact_name']) ?> — <?= esc($partner['email']) ?>, <?= esc($partner['phone']) ?></div></div>
            <div><div class="text-xs text-slate-500">Fulfillment</div><div><?= esc($partner['fulfillment_type']) ?></div></div>
            <div><div class="text-xs text-slate-500">Commission</div><div><?= esc($partner['commission_pct']) ?>%</div></div>
            <div><div class="text-xs text-slate-500">Status</div><div><?= esc($partner['status']) ?></div></div>
        </div>
        <p class="mt-4 text-xs text-slate-500">To update these details, contact Khoobie support.</p>
    <?php else: ?>
        <p class="text-slate-500">No partner record linked to your account.</p>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
