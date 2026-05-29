<?= $this->extend('layouts/partner') ?>
<?= $this->section('content') ?>

<h1 class="text-xl font-black">Orders to fulfill</h1>
<form method="get" class="mt-3 flex gap-2 text-sm">
    <select name="status" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200">
        <option value="">All</option>
        <?php foreach (['pending','allocated','packed','shipped','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="mt-4 bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">Order</th>
                <th class="px-4 py-3 text-left">Buyer</th>
                <th class="px-4 py-3 text-left">Item</th>
                <th class="px-4 py-3 text-right">Qty</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($items as $it): $snap = json_decode($it['product_snapshot'] ?? '{}', true) ?: []; ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs"><?= esc($it['order_number']) ?></td>
                    <td class="px-4 py-3"><div class="font-semibold"><?= esc($it['buyer_name']) ?></div><div class="text-xs text-slate-500"><?= esc($it['buyer_phone']) ?></div></td>
                    <td class="px-4 py-3"><?= esc($snap['name'] ?? '') ?></td>
                    <td class="px-4 py-3 text-right font-bold"><?= (int) $it['qty'] ?></td>
                    <td class="px-4 py-3 text-xs"><?= esc(str_replace('_',' ',$it['fulfillment_status'])) ?></td>
                    <td class="px-4 py-3 text-right"><a href="<?= base_url('partner/orders/' . $it['order_id']) ?>" class="text-brand-600 font-semibold text-xs">Open &rarr;</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Nothing to fulfill.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
