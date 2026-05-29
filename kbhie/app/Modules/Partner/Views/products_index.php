<?= $this->extend('layouts/partner') ?>
<?= $this->section('content') ?>

<div class="flex items-end justify-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="text-xl font-black">My Products</h1>
        <p class="text-sm text-slate-500 mt-0.5"><?= count($rows) ?> total · new submissions land as draft, admin reviews within 24h.</p>
    </div>
    <a href="<?= base_url('partner/products/new') ?>" class="btn-primary">+ New product</a>
</div>

<?php if (session('success')): ?>
    <div class="mb-4 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left"></th>
                <th class="px-4 py-3 text-left">Product</th>
                <th class="px-4 py-3 text-left">SKU</th>
                <th class="px-4 py-3 text-right">Price</th>
                <th class="px-4 py-3 text-right">Stock</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $r): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-3 py-3">
                        <?php if ($r['hero_image']): ?>
                            <img src="<?= esc($r['hero_image']) ?>" class="w-10 h-10 rounded object-cover" alt="">
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 font-semibold"><?= esc($r['name']) ?></td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-500"><?= esc($r['sku']) ?></td>
                    <td class="px-4 py-3 text-right tabular-nums"><?= kb_money((int) ($r['price'] ?? 0)) ?></td>
                    <td class="px-4 py-3 text-right tabular-nums"><?= (int) ($r['stock'] ?? 0) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-xs font-bold capitalize <?= $r['status']==='active'?'bg-emerald-100 text-emerald-700':($r['status']==='draft'?'bg-amber-100 text-amber-700':'bg-slate-100 text-slate-700') ?>"><?= esc($r['status']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="<?= base_url('partner/products/' . $r['id'] . '/edit') ?>" class="text-brand-600 font-bold text-xs hover:underline">Edit →</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">No products yet. <a href="<?= base_url('partner/products/new') ?>" class="text-brand-600 font-bold">Add your first →</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
