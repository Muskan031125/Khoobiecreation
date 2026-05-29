<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h1 class="text-xl font-black">Last 30 days</h1>

<div class="mt-4 grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-bold">Conversion funnel</h2>
        <ul class="mt-3 space-y-2 text-sm">
            <?php foreach ($funnel as $stage => $count): ?>
                <li class="flex justify-between"><span class="capitalize"><?= str_replace('_',' ',$stage) ?></span><span class="font-bold"><?= number_format($count) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-bold">Top products by qty</h2>
        <ul class="mt-3 space-y-1 text-sm">
            <?php foreach ($topProducts as $p): ?>
                <li class="flex justify-between border-b border-slate-100 py-1">
                    <span><?= esc($p['name']) ?></span>
                    <span class="font-bold"><?= number_format($p['qty']) ?></span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($topProducts)): ?>
                <li class="text-slate-400">No orders yet.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="mt-4 bg-white rounded-2xl shadow-sm p-5 overflow-x-auto">
    <h2 class="font-bold">Daily sales</h2>
    <table class="w-full text-sm mt-3">
        <thead class="text-xs uppercase tracking-wide text-slate-500">
            <tr><th class="px-3 py-2 text-left">Day</th><th class="px-3 py-2 text-right">Orders</th><th class="px-3 py-2 text-right">Revenue</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($sales as $r): ?>
                <tr><td class="px-3 py-1.5"><?= esc($r['day']) ?></td><td class="px-3 py-1.5 text-right"><?= number_format($r['orders']) ?></td><td class="px-3 py-1.5 text-right"><?= kb_money((int)($r['revenue'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
                <tr><td colspan="3" class="px-3 py-4 text-center text-slate-400">No sales in the last 30 days.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
