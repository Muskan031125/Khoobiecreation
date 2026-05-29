<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$attr = json_decode($row['attribution'] ?? '{}', true) ?: [];
$meta = json_decode($row['metadata']    ?? '{}', true) ?: [];
?>

<div class="max-w-3xl space-y-4">
    <a href="<?= base_url('admin/leads') ?>" class="text-sm text-slate-500 hover:underline">← All leads</a>

    <div class="bg-white rounded-2xl shadow-sm p-6 space-y-3">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Lead #<?= $row['id'] ?> · <?= esc($row['kind']) ?></div>
                <h1 class="text-2xl font-display font-black mt-1"><?= esc($row['name'] ?: 'Anonymous') ?></h1>
                <div class="text-sm text-slate-600 mt-0.5"><?= esc($row['phone']) ?> · <?= esc($row['email']) ?></div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-violet-100 text-violet-700"><?= esc($row['status']) ?></span>
        </div>

        <div class="grid sm:grid-cols-2 gap-3 pt-3 border-t border-slate-100">
            <div><span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Created</span><div class="text-sm font-semibold"><?= date('j M Y, g:i A', strtotime($row['created_at'])) ?></div></div>
            <div><span class="text-xs text-slate-500 font-bold uppercase tracking-wider">OTP verified</span><div class="text-sm font-semibold"><?= $row['verified_at'] ? '✓ ' . date('j M, g:i A', strtotime($row['verified_at'])) : 'Not yet' ?></div></div>
            <?php if ($row['child_name']): ?><div><span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Child name</span><div class="text-sm font-semibold"><?= esc($row['child_name']) ?></div></div><?php endif; ?>
            <?php if ($row['child_age']): ?><div><span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Child age</span><div class="text-sm font-semibold"><?= (int) $row['child_age'] ?> years</div></div><?php endif; ?>
            <?php if ($row['preferred_slot']): ?><div><span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Preferred slot</span><div class="text-sm font-semibold"><?= esc($row['preferred_slot']) ?></div></div><?php endif; ?>
        </div>

        <?php if ($row['message']): ?>
            <div class="pt-3 border-t border-slate-100">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Message</span>
                <p class="mt-1 text-sm text-slate-700"><?= nl2br(esc($row['message'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($row['product_name']): ?>
            <div class="pt-3 border-t border-slate-100">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Product</span>
                <a href="<?= base_url('product/' . $row['product_slug']) ?>" target="_blank" class="block mt-1 text-brand-600 font-bold hover:underline"><?= esc($row['product_name']) ?> →</a>
                <div class="text-xs text-slate-500 capitalize"><?= esc($row['product_type']) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($row['amount_due']): ?>
            <div class="pt-3 border-t border-slate-100">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Payment</span>
                <div class="mt-1 text-sm">
                    Advance paid: <strong class="text-emerald-700">₹<?= number_format(round($row['amount_paid']/100)) ?></strong>
                    of <strong>₹<?= number_format(round($row['amount_due']/100)) ?></strong> total
                </div>
                <?php if ($row['gateway_ref']): ?><div class="text-[10px] font-mono text-slate-400 mt-0.5"><?= esc($row['gateway_ref']) ?></div><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Attribution panel -->
    <?php if (! empty($attr)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h2 class="font-bold text-sm">📍 Attribution</h2>
            <dl class="mt-2 grid grid-cols-2 gap-2 text-xs">
                <?php foreach ($attr as $k => $v): if (! $v) continue; ?>
                    <div><dt class="text-slate-500 uppercase tracking-wider font-bold"><?= esc($k) ?></dt><dd class="text-slate-900 font-semibold"><?= esc(is_string($v) ? $v : json_encode($v)) ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </div>
    <?php endif; ?>

    <!-- Status actions -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-bold text-sm">Update status</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php foreach (['contacted','converted','cancelled','no_show'] as $s): ?>
                <form method="post" action="<?= base_url('admin/leads/' . $row['id'] . '/status') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="<?= $s ?>">
                    <button class="px-4 py-2 rounded-lg <?= $row['status'] === $s ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?> text-sm font-bold"><?= ucfirst($s) ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
