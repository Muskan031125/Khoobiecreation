<?= $this->extend('layouts/partner') ?>
<?= $this->section('content') ?>

<h1 class="text-xl font-black">Payouts</h1>
<div class="mt-4 bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr><th class="px-4 py-3 text-left">Period</th><th class="px-4 py-3 text-right">Gross</th><th class="px-4 py-3 text-right">Commission</th><th class="px-4 py-3 text-right">Net</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">UTR</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="px-4 py-3"><?= esc($r['period_start']) ?> – <?= esc($r['period_end']) ?></td>
                    <td class="px-4 py-3 text-right"><?= kb_money((int)($r['gross_amount'])) ?></td>
                    <td class="px-4 py-3 text-right"><?= kb_money((int)($r['commission'])) ?></td>
                    <td class="px-4 py-3 text-right font-bold"><?= kb_money((int)($r['net_payable'])) ?></td>
                    <td class="px-4 py-3 text-xs"><?= esc($r['status']) ?></td>
                    <td class="px-4 py-3 font-mono text-xs"><?= esc($r['utr']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No payouts yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
