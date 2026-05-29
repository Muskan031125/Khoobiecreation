<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="grid lg:grid-cols-[1fr_320px] gap-4 max-w-6xl">

    <div class="space-y-4">
        <h1 class="text-2xl font-black">Warehouse Routing Zones</h1>
        <p class="text-sm text-slate-500">Map pincode patterns to warehouses with priority. Longer patterns win when overlapping. <code class="font-mono bg-slate-100 px-1 rounded">%</code> is a wildcard.</p>

        <?php if (session('success')): ?>
            <div class="px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr><th class="text-left p-3">Pattern</th><th class="text-left p-3">Warehouse</th><th class="text-right p-3">Priority</th><th class="text-right p-3">ETA</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr class="border-b last:border-0 hover:bg-slate-50">
                            <td class="p-3 font-mono font-bold"><?= esc($r['pincode_pattern']) ?></td>
                            <td class="p-3"><?= esc($r['warehouse_name']) ?> <span class="text-xs text-slate-500"><?= esc($r['city']) ?></span></td>
                            <td class="p-3 text-right tabular-nums"><?= (int) $r['priority'] ?></td>
                            <td class="p-3 text-right tabular-nums"><?= (int) $r['estimated_days'] ?>d</td>
                            <td class="p-3 text-right">
                                <form method="post" action="<?= base_url('admin/warehouse-zones/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('Remove this zone?')">
                                    <?= csrf_field() ?>
                                    <button class="text-xs text-rose-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Test pincode -->
        <div class="bg-amber-50 border-2 border-dashed border-amber-300 rounded-2xl p-4" x-data="{ pin: '', result: null }">
            <h2 class="font-bold text-amber-900">🧪 Test a pincode</h2>
            <div class="mt-2 flex gap-2">
                <input x-model="pin" placeholder="e.g. 400050" maxlength="6" class="flex-1 px-3 py-2 rounded-lg border-2 border-amber-200">
                <button @click="fetch('<?= base_url('admin/warehouse-zones/test') ?>?pin=' + pin).then(r=>r.json()).then(j => result = j)" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-bold">Route</button>
            </div>
            <div x-show="result" x-cloak class="mt-3 px-3 py-2 rounded-lg bg-white text-sm font-mono" x-text="JSON.stringify(result, null, 2)"></div>
        </div>
    </div>

    <!-- Add form -->
    <aside class="space-y-4">
        <form method="post" action="<?= base_url('admin/warehouse-zones/save') ?>" class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <?= csrf_field() ?>
            <h2 class="font-bold">+ Add zone</h2>
            <select name="warehouse_id" required class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                <option value="">Pick warehouse…</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= (int) $w['id'] ?>"><?= esc($w['name']) ?> · <?= esc($w['city']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="pincode_pattern" required placeholder="Pattern (4000% / 11% / %)" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 font-mono">
            <div class="grid grid-cols-2 gap-2">
                <input name="priority" type="number" value="50" placeholder="Priority" class="px-3 py-2 rounded-lg border-2 border-slate-200">
                <input name="estimated_days" type="number" value="4" placeholder="ETA days" class="px-3 py-2 rounded-lg border-2 border-slate-200">
            </div>
            <button class="w-full btn-primary">Add zone</button>
            <p class="text-[10px] text-slate-500">Lower priority wins on ties. Longer patterns always win first.</p>
        </form>
    </aside>
</div>

<?= $this->endSection() ?>
