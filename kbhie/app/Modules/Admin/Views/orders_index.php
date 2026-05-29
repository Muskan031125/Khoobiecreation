<?= $this->extend('layouts/admin') ?>

<?php
$extraQs = ['q' => $q, 'status' => $status, 'per_page' => $perPage];
$sortLink = function (string $col, string $label) use ($sort, $sortDir, $extraQs) {
    $newDir = ($sort === $col && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    $href = base_url('admin/orders?' . http_build_query(array_merge($extraQs, ['sort' => $col, 'dir' => $newDir])));
    $caret = ($sort === $col)
        ? '<span class="text-brand-600">' . ($sortDir === 'ASC' ? '▲' : '▼') . '</span>'
        : '<span class="text-slate-300 text-[10px]">⇅</span>';
    return '<a href="' . esc($href, 'attr') . '" class="inline-flex items-center gap-1 hover:text-slate-900">' . esc($label) . ' ' . $caret . '</a>';
};
?>

<?= $this->section('actions') ?>
    <form method="get" class="flex flex-wrap items-center gap-2">
        <input name="q" value="<?= esc($q) ?>" placeholder="Order #, phone, email, name…"
               class="px-3 py-2 rounded-lg border border-slate-200 text-sm w-56 sm:w-72 focus:outline-none focus:border-brand-400">
        <select name="status" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white">
            <option value="">All statuses</option>
            <?php foreach (['pending_payment','pending_confirmation','confirmed','processing','partially_shipped','shipped','delivered','cancelled','returned','refunded','failed'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="per_page" value="<?= esc($perPage) ?>">
        <button class="px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold">Apply</button>
        <?php if ($q || $status): ?>
            <a href="<?= base_url('admin/orders') ?>" class="text-xs text-slate-500 hover:underline">Clear</a>
        <?php endif; ?>
    </form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('App\Modules\Admin\Views\_bulk_toolbar', [
    'table' => 'orders',
    'ids'   => array_map(fn ($o) => (int) $o['id'], $orders),
    'actions' => [
        ['key' => 'confirm', 'label' => '✓ Confirm', 'cls' => 'bg-emerald-500'],
        ['key' => 'cancel',  'label' => '✕ Cancel',  'cls' => 'bg-rose-500', 'confirm' => 'Cancel selected orders?'],
    ],
]) ?>

<div x-data="bulk({table:'orders', rows:<?= json_encode(array_map(fn ($o) => (int) $o['id'], $orders)) ?>})" class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-3 py-3 text-left w-8"><input type="checkbox" @click="toggleAll($event.target.checked)" :checked="selected.length === rows.length && rows.length > 0"></th>
                <th class="px-4 py-3 text-left"><?= $sortLink('order_number', '#') ?></th>
                <th class="px-4 py-3 text-left"><?= $sortLink('created_at', 'Placed') ?></th>
                <th class="px-4 py-3 text-left"><?= $sortLink('name', 'Customer') ?></th>
                <th class="px-4 py-3 text-right"><?= $sortLink('grand_total', 'Total') ?></th>
                <th class="px-4 py-3 text-left">Payment</th>
                <th class="px-4 py-3 text-left"><?= $sortLink('status', 'Status') ?></th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($orders as $o): ?>
                <tr class="hover:bg-slate-50" :class="selected.includes(<?= (int) $o['id'] ?>) ? 'bg-brand-50' : ''">
                    <td class="px-3 py-3"><input type="checkbox" :value="<?= (int) $o['id'] ?>" x-model="selected"></td>
                    <td class="px-4 py-3 font-mono text-xs">
                        <a href="<?= base_url('admin/orders/' . $o['id']) ?>" class="text-brand-600 hover:underline font-bold">#<?= esc($o['order_number']) ?></a>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500" title="<?= esc(kb_date($o['created_at'], true)) ?>"><?= kb_relative($o['created_at']) ?></td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-900"><?= esc($o['name']) ?></div>
                        <div class="text-xs text-slate-500"><?= kb_phone($o['phone']) ?></div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold"><?= kb_money((int) $o['grand_total']) ?></td>
                    <td class="px-4 py-3 text-xs text-slate-600"><?= esc($o['payment_method'] ?: '—') ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 text-xs rounded-full font-semibold
                            <?= in_array($o['status'], ['pending_payment','pending_confirmation']) ? 'bg-amber-100 text-amber-700' :
                                (in_array($o['status'], ['cancelled','failed','refunded']) ? 'bg-red-100 text-red-700' :
                                ($o['status'] === 'delivered' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700')) ?>">
                            <?= esc(str_replace('_', ' ', $o['status'])) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right"><a href="<?= base_url('admin/orders/' . $o['id']) ?>" class="text-brand-600 font-semibold text-xs">Open &rarr;</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
                <tr><td colspan="7" class="px-4 py-16 text-center">
                    <div class="text-4xl">📋</div>
                    <div class="mt-2 text-slate-500">
                        <?php if ($q || $status): ?>
                            No orders match your filters.
                            <a href="<?= base_url('admin/orders') ?>" class="text-brand-600 font-semibold hover:underline">Clear filters</a>
                        <?php else: ?>
                            No orders yet — they'll appear here as customers check out.
                        <?php endif; ?>
                    </div>
                </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->include('partials/_admin_pagination') ?>

<?= $this->endSection() ?>
