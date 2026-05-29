<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
    <form method="get" class="flex items-center gap-2">
        <input name="q" value="<?= esc($q) ?>" placeholder="Search <?= esc(strtolower($title)) ?>…"
               class="px-3 py-2 rounded-lg border border-slate-200 text-sm w-56 sm:w-72 focus:outline-none focus:border-brand-400">
        <input type="hidden" name="per_page" value="<?= esc($perPage) ?>">
        <button class="px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold">Search</button>
        <?php if ($q): ?>
            <a href="<?= base_url('admin/' . str_replace('_','-',$table)) ?>" class="text-xs text-slate-500 hover:underline">Clear</a>
        <?php endif; ?>
    </form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <?php
                $extraQs = ['q' => $q, 'per_page' => $perPage];
                $sortableSet = array_flip($sortableCols);
                foreach ($cols as $c):
                    $isSortable = isset($sortableSet[$c]);
                    $newDir = ($sort === $c && $sortDir === 'ASC') ? 'DESC' : 'ASC';
                    $href   = base_url('admin/' . str_replace('_','-',$table) . '?' . http_build_query(array_merge($extraQs, ['sort' => $c, 'dir' => $newDir])));
                ?>
                    <th class="px-4 py-3 text-left font-semibold">
                        <?php if ($isSortable): ?>
                            <a href="<?= esc($href) ?>" class="inline-flex items-center gap-1 hover:text-slate-900">
                                <?= esc(str_replace('_',' ',$c)) ?>
                                <?php if ($sort === $c): ?>
                                    <span class="text-brand-600"><?= $sortDir === 'ASC' ? '▲' : '▼' ?></span>
                                <?php else: ?>
                                    <span class="text-slate-300 text-[10px]">⇅</span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <?= esc(str_replace('_',' ',$c)) ?>
                        <?php endif; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $row): ?>
                <tr class="hover:bg-slate-50">
                    <?php foreach ($cols as $c): $val = $row[$c] ?? ''; ?>
                        <td class="px-4 py-2.5 align-middle">
                            <?php if (in_array($c, ['price','amount','balance','revenue','initial_value','grand_total','total_amount','gross_amount','commission','net_payable','lifetime_earnings'], true) && is_numeric($val)): ?>
                                <?= kb_money((int) $val) ?>
                            <?php elseif (in_array($c, ['is_active','is_default','is_featured','is_cancelled','consent_email','consent_whatsapp'], true)): ?>
                                <?= $val ? '<span class="text-emerald-600">✓</span>' : '<span class="text-slate-300">—</span>' ?>
                            <?php elseif (str_ends_with($c, '_at') && $val): ?>
                                <span class="text-xs text-slate-500" title="<?= esc(kb_date($val, true)) ?>"><?= kb_relative($val) ?></span>
                            <?php elseif (in_array($c, ['status','fulfillment_type','tier'], true)): ?>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold
                                    <?= in_array($val, ['active','published','sent','paid','captured','confirmed','delivered']) ? 'bg-emerald-100 text-emerald-700' :
                                        (in_array($val, ['pending','queued','draft','pending_confirmation','pending_payment']) ? 'bg-amber-100 text-amber-700' :
                                        (in_array($val, ['failed','cancelled','suspended','disabled']) ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700')) ?>">
                                    <?= esc(str_replace('_',' ',$val)) ?>
                                </span>
                            <?php else: ?>
                                <span class="<?= strlen((string) $val) > 40 ? 'block max-w-xs truncate' : '' ?>" title="<?= esc((string) $val) ?>"><?= esc((string) $val) ?></span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($cols) ?>" class="px-4 py-16 text-center">
                    <div class="text-4xl">📭</div>
                    <div class="mt-2 text-slate-500">
                        <?php if ($q): ?>
                            No <?= esc(strtolower($title)) ?> match "<?= esc($q) ?>".
                            <a href="<?= base_url('admin/' . str_replace('_','-',$table)) ?>" class="text-brand-600 font-semibold hover:underline">Clear search</a>
                        <?php else: ?>
                            No <?= esc(strtolower($title)) ?> yet.
                        <?php endif; ?>
                    </div>
                </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->include('partials/_admin_pagination') ?>

<?= $this->endSection() ?>
