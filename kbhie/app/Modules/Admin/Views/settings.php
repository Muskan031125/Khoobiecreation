<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<form method="post" action="<?= base_url('admin/settings') ?>" class="space-y-6">
    <?= csrf_field() ?>
    <?php foreach ($grouped as $group => $rows): ?>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h2 class="font-black text-lg capitalize"><?= esc(str_replace('_',' ',$group)) ?></h2>
            <div class="mt-3 divide-y divide-slate-100">
                <?php foreach ($rows as $r): ?>
                    <div class="py-3 grid sm:grid-cols-2 gap-3 items-center">
                        <div>
                            <div class="font-semibold text-sm"><?= esc($r['label'] ?: $r['key']) ?></div>
                            <div class="text-xs text-slate-500"><?= esc($r['description'] ?: $r['key']) ?></div>
                            <?php if ($r['is_public']): ?><div class="text-[10px] mt-0.5 inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">Public</div><?php endif; ?>
                        </div>
                        <?php if ($r['value_type'] === 'bool'): ?>
                            <select name="settings[<?= $r['id'] ?>]" class="px-3 py-2 rounded-lg border border-slate-200 text-sm">
                                <option value="1" <?= $r['value'] == '1' ? 'selected' : '' ?>>Yes</option>
                                <option value="0" <?= $r['value'] == '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        <?php elseif ($r['value_type'] === 'json'): ?>
                            <textarea name="settings[<?= $r['id'] ?>]" rows="2" class="px-3 py-2 rounded-lg border border-slate-200 text-sm font-mono"><?= esc($r['value']) ?></textarea>
                        <?php else: ?>
                            <input name="settings[<?= $r['id'] ?>]" value="<?= esc($r['value']) ?>" class="px-3 py-2 rounded-lg border border-slate-200 text-sm" type="<?= $r['value_type'] === 'int' ? 'number' : 'text' ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <button class="btn-primary">Save all settings</button>
</form>

<?= $this->endSection() ?>
