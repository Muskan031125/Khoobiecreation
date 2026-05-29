<?= $this->extend('layouts/admin') ?>

<?php
$extraQs = ['q' => $q, 'status' => $status, 'per_page' => $perPage];
$sortLink = function (string $col, string $label) use ($sort, $sortDir, $extraQs) {
    $newDir = ($sort === $col && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    $href = base_url('admin/products?' . http_build_query(array_merge($extraQs, ['sort' => $col, 'dir' => $newDir])));
    $caret = ($sort === $col)
        ? '<span class="text-brand-600">' . ($sortDir === 'ASC' ? '▲' : '▼') . '</span>'
        : '<span class="text-slate-300 text-[10px]">⇅</span>';
    return '<a href="' . esc($href, 'attr') . '" class="inline-flex items-center gap-1 hover:text-slate-900">' . esc($label) . ' ' . $caret . '</a>';
};
?>

<?= $this->section('actions') ?>
    <form method="get" class="flex flex-wrap items-center gap-2">
        <input name="q" value="<?= esc($q) ?>" placeholder="Search by name or SKU…"
               class="px-3 py-2 rounded-lg border border-slate-200 text-sm w-48 sm:w-64 focus:outline-none focus:border-brand-400">
        <select name="status" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white">
            <option value="">All statuses</option>
            <?php foreach (['draft','active','out_of_stock','discontinued'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="per_page" value="<?= esc($perPage) ?>">
        <button class="px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold">Search</button>
        <a href="<?= base_url('admin/products/new') ?>" class="px-3 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold">+ New product</a>
    </form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Bulk action toolbar — visible when any row is selected -->
<div x-data="bulkActions()" @click.outside="confirmAction = null">
    <div x-show="selected.length > 0" x-transition x-cloak
         class="bg-slate-900 text-white rounded-2xl p-3 mb-3 flex items-center gap-2 flex-wrap shadow-cta">
        <span class="px-2 py-1 rounded bg-white/10 text-xs font-bold">
            <span x-text="selected.length"></span> selected
        </span>
        <button @click="execute('activate')"   class="px-3 py-1.5 rounded-md bg-emerald-500 hover:bg-emerald-600 text-xs font-bold">✓ Activate</button>
        <button @click="execute('deactivate')" class="px-3 py-1.5 rounded-md bg-amber-500  hover:bg-amber-600 text-xs font-bold">○ Deactivate</button>
        <button @click="execute('feature')"    class="px-3 py-1.5 rounded-md bg-violet-500 hover:bg-violet-600 text-xs font-bold">⭐ Feature</button>
        <button @click="confirmAction = 'change_price'" class="px-3 py-1.5 rounded-md bg-sky-500 hover:bg-sky-600 text-xs font-bold">₹ Change price</button>
        <button @click="exportCsv()"           class="px-3 py-1.5 rounded-md bg-slate-700 hover:bg-slate-600 text-xs font-bold">⬇ Export CSV</button>
        <button @click="confirmAction = 'delete'"      class="px-3 py-1.5 rounded-md bg-rose-500 hover:bg-rose-600 text-xs font-bold ml-auto">🗑 Delete</button>
    </div>

    <!-- Confirm prompt for destructive / parameterised actions -->
    <div x-show="confirmAction === 'delete'" x-cloak class="bg-rose-50 border-2 border-dashed border-rose-300 rounded-2xl p-3 mb-3 flex items-center gap-2 text-sm">
        <span>Delete <span x-text="selected.length" class="font-bold"></span> products? They'll be soft-deleted (recoverable).</span>
        <button @click="execute('delete'); confirmAction = null" class="ml-auto px-3 py-1.5 rounded-md bg-rose-600 text-white text-xs font-bold">Confirm delete</button>
        <button @click="confirmAction = null" class="px-3 py-1.5 rounded-md bg-white border border-slate-200 text-xs font-bold">Cancel</button>
    </div>
    <div x-show="confirmAction === 'change_price'" x-cloak class="bg-sky-50 border-2 border-dashed border-sky-300 rounded-2xl p-3 mb-3 flex items-center gap-2 text-sm">
        <span>Adjust price by</span>
        <input x-model.number="pricePct" type="number" step="1" placeholder="%" class="w-20 px-2 py-1 rounded border-2 border-sky-200 text-center">
        <span>% (e.g. -10 for 10% off, +15 for 15% up)</span>
        <button @click="execute('change_price', { change_pct: pricePct }); confirmAction = null" class="ml-auto px-3 py-1.5 rounded-md bg-sky-600 text-white text-xs font-bold">Apply</button>
        <button @click="confirmAction = null" class="px-3 py-1.5 rounded-md bg-white border border-slate-200 text-xs font-bold">Cancel</button>
    </div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-3 py-3 text-left w-8">
                    <input type="checkbox" @click="toggleAll($event.target.checked)" :checked="selected.length === rows.length && rows.length > 0">
                </th>
                <th class="px-3 py-3 text-left w-12"></th>
                <th class="px-3 py-3 text-left"><?= $sortLink('sku', 'SKU') ?></th>
                <th class="px-3 py-3 text-left"><?= $sortLink('name', 'Name') ?></th>
                <th class="px-3 py-3 text-left"><?= $sortLink('type', 'Type') ?></th>
                <th class="px-3 py-3 text-right"><?= $sortLink('price', 'Price') ?></th>
                <th class="px-3 py-3 text-right"><?= $sortLink('stock', 'Stock') ?></th>
                <th class="px-3 py-3 text-left"><?= $sortLink('status', 'Status') ?></th>
                <th class="px-3 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($products as $p):
                $img = $p['hero_image'] ?? null;
                $imgSrc = $img ? (preg_match('#^https?://#', $img) ? $img : base_url($img)) : null;
                $stock = (int) ($p['stock'] ?? 0);
            ?>
                <tr class="hover:bg-slate-50" :class="selected.includes(<?= (int) $p['id'] ?>) ? 'bg-brand-50' : ''">
                    <td class="px-3 py-2.5">
                        <input type="checkbox" :value="<?= (int) $p['id'] ?>" x-model="selected">
                    </td>
                    <td class="px-3 py-2.5">
                        <?php if ($imgSrc): ?>
                            <img src="<?= esc($imgSrc) ?>" alt="" class="w-10 h-10 rounded-md object-cover">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-md bg-slate-100 flex items-center justify-center text-slate-300">🎁</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 font-mono text-xs text-slate-600"><?= esc($p['sku']) ?></td>
                    <td class="px-3 py-2.5">
                        <a href="<?= base_url('admin/products/' . $p['id'] . '/edit') ?>" class="font-semibold text-slate-900 hover:text-brand-600"><?= esc($p['name']) ?></a>
                        <?php if (! empty($p['is_featured'])): ?> <span class="ml-1 text-[10px] uppercase font-bold text-amber-600">★ Featured</span><?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-xs text-slate-600"><?= esc($p['type']) ?></td>
                    <td class="px-3 py-2.5 text-right font-semibold"><?= kb_money((int) ($p['price'] ?? 0)) ?></td>
                    <td class="px-3 py-2.5 text-right">
                        <span class="<?= $stock === 0 ? 'text-red-600 font-bold' : ($stock <= 10 ? 'text-amber-600 font-semibold' : 'text-slate-700') ?>">
                            <?= number_format($stock) ?>
                        </span>
                    </td>
                    <td class="px-3 py-2.5">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                            <?= $p['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' :
                                ($p['status'] === 'out_of_stock' ? 'bg-amber-100 text-amber-700' :
                                ($p['status'] === 'discontinued' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700')) ?>">
                            <?= esc(str_replace('_',' ', $p['status'])) ?>
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                        <a href="<?= base_url('admin/products/' . $p['id'] . '/edit') ?>" class="text-brand-600 font-semibold text-xs">Edit &rarr;</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <tr><td colspan="8" class="px-4 py-16 text-center">
                    <div class="text-4xl">📦</div>
                    <div class="mt-2 text-slate-500">
                        <?php if ($q || $status): ?>
                            No products match your filters.
                            <a href="<?= base_url('admin/products') ?>" class="text-brand-600 font-semibold hover:underline">Clear filters</a>
                        <?php else: ?>
                            No products yet. <a href="<?= base_url('admin/products/new') ?>" class="text-brand-600 font-semibold hover:underline">+ Add your first product</a>
                        <?php endif; ?>
                    </div>
                </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->include('partials/_admin_pagination') ?>
</div><!-- close bulkActions wrapper -->

<script>
function bulkActions() {
    return {
        rows: <?= json_encode(array_map(fn ($p) => (int) $p['id'], $products)) ?>,
        selected: [],
        confirmAction: null,
        pricePct: -10,
        toggleAll(checked) { this.selected = checked ? [...this.rows] : []; },
        async execute(action, extra = {}) {
            if (! this.selected.length) return;
            const fd = new FormData();
            fd.append('action', action);
            this.selected.forEach(id => fd.append('ids[]', id));
            Object.entries(extra).forEach(([k, v]) => fd.append(k, v));
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const r = await fetch('<?= base_url('admin/bulk/products') ?>', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
            const j = await r.json();
            if (j.ok) {
                location.reload();
            } else {
                alert('Bulk action failed: ' + (j.error || 'unknown'));
            }
        },
        exportCsv() {
            if (! this.selected.length) return;
            location.href = '<?= base_url('admin/bulk/products/export') ?>?ids=' + this.selected.join(',');
        }
    }
}
</script>

<?= $this->endSection() ?>
