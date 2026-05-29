<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $colors = ['requested'=>'bg-amber-100 text-amber-700','approved'=>'bg-sky-100 text-sky-700','rejected'=>'bg-rose-100 text-rose-700','received'=>'bg-violet-100 text-violet-700','refunded'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-slate-100 text-slate-700']; ?>

<div class="flex items-end justify-between mb-4">
    <div>
        <h1 class="text-2xl font-black">Returns</h1>
        <p class="text-sm text-slate-500"><?= $counts['_total'] ?? 0 ?> total · <?= $counts['requested'] ?? 0 ?> pending review</p>
    </div>
</div>

<div class="flex flex-wrap gap-1.5 mb-4">
    <a href="?" class="px-2.5 py-1 rounded-full text-xs font-bold <?= ! $status ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">All (<?= $counts['_total'] ?? 0 ?>)</a>
    <?php foreach (['requested','approved','received','refunded','rejected'] as $s): ?>
        <a href="?status=<?= $s ?>" class="px-2.5 py-1 rounded-full text-xs font-bold <?= $status === $s ? 'bg-brand-500 text-white' : ($colors[$s] ?? 'bg-slate-100 text-slate-700') . ' hover:opacity-80' ?>">
            <?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)
        </a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
            <tr><th class="text-left p-3">Return #</th><th class="text-left p-3">Order</th><th class="text-left p-3">Customer</th><th class="text-left p-3">Reason</th><th class="text-left p-3">Status</th><th class="text-right p-3">Refund</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="p-8 text-center text-slate-400">No returns match.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr class="border-b last:border-0 hover:bg-slate-50">
                    <td class="p-3 font-mono text-xs"><?= esc($r['return_number']) ?></td>
                    <td class="p-3 font-mono text-xs">
                        <a href="<?= base_url('admin/orders/' . $r['order_id']) ?>" class="text-brand-600 hover:underline"><?= esc($r['order_number']) ?></a>
                    </td>
                    <td class="p-3">
                        <div class="font-semibold"><?= esc($r['customer_name']) ?></div>
                        <div class="text-xs text-slate-500"><?= esc($r['phone']) ?></div>
                    </td>
                    <td class="p-3"><span class="text-xs"><?= esc($r['reason']) ?></span></td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded text-xs font-bold <?= $colors[$r['status']] ?? 'bg-slate-100 text-slate-700' ?>"><?= esc($r['status']) ?></span>
                    </td>
                    <td class="p-3 text-right tabular-nums">
                        <?= $r['refund_amount'] ? kb_money((int) $r['refund_amount']) : '—' ?>
                    </td>
                    <td class="p-3 text-right">
                        <a href="<?= base_url('admin/returns/' . $r['id']) ?>" class="text-xs font-bold text-brand-600 hover:underline">Open →</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
