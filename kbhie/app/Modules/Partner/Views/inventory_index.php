<?= $this->extend('layouts/partner') ?>
<?= $this->section('content') ?>

<h1 class="text-xl font-black">Inventory</h1>
<div class="mt-4 bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr><th class="px-4 py-3 text-left">Product</th><th class="px-4 py-3 text-left">SKU</th><th class="px-4 py-3 text-left">Warehouse</th><th class="px-4 py-3 text-right">On hand</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="px-4 py-3"><?= esc($r['product_name']) ?></td>
                    <td class="px-4 py-3 font-mono text-xs"><?= esc($r['variant_sku']) ?></td>
                    <td class="px-4 py-3"><?= esc($r['warehouse_name']) ?></td>
                    <td class="px-4 py-3 text-right font-bold"><?= (int) $r['qty_on_hand'] ?></td>
                    <td class="px-4 py-3 text-right">
                        <form method="post" action="<?= base_url('partner/inventory/update') ?>" class="flex gap-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="inventory_id" value="<?= (int) $r['id'] ?>">
                            <input name="qty_on_hand" type="number" min="0" class="w-20 px-2 py-1 rounded border border-slate-200 text-sm text-right">
                            <button class="px-2 py-1 rounded bg-slate-900 text-white text-xs font-semibold">Set</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No inventory configured.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
