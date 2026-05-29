<?php
/**
 * Reusable bulk-action toolbar. Caller passes:
 *   $table  — string  (products|orders|intents|blogs)
 *   $actions — array of ['key' => 'activate', 'label' => '✓ Activate', 'cls' => 'bg-emerald-500']
 *   $ids    — array of int IDs for the current page
 */
?>
<div x-data="bulk(<?= htmlspecialchars(json_encode(['table'=>$table, 'rows'=>$ids]), ENT_QUOTES) ?>)">
    <div x-show="selected.length > 0" x-transition x-cloak
         class="bg-slate-900 text-white rounded-2xl p-3 mb-3 flex items-center gap-2 flex-wrap shadow-cta">
        <span class="px-2 py-1 rounded bg-white/10 text-xs font-bold"><span x-text="selected.length"></span> selected</span>
        <?php foreach ($actions as $a): ?>
            <button @click="execute('<?= esc($a['key'], 'attr') ?>'<?= ! empty($a['confirm']) ? ', ' . json_encode($a['confirm']) : '' ?>)"
                    class="px-3 py-1.5 rounded-md <?= esc($a['cls'], 'attr') ?> text-xs font-bold hover:opacity-90"><?= esc($a['label']) ?></button>
        <?php endforeach; ?>
        <button @click="exportCsv()" class="px-3 py-1.5 rounded-md bg-slate-700 text-xs font-bold ml-auto">⬇ Export CSV</button>
    </div>
</div>

<?php // Render the JS only once per page
if (! defined('KB_BULK_JS_RENDERED')): define('KB_BULK_JS_RENDERED', 1); ?>
<script>
function bulk(opts) {
    return {
        table: opts.table,
        rows: opts.rows,
        selected: [],
        toggleAll(checked) { this.selected = checked ? [...this.rows] : []; },
        async execute(action, confirmMsg = null) {
            if (! this.selected.length) return;
            if (confirmMsg && ! confirm(confirmMsg)) return;
            const fd = new FormData();
            fd.append('action', action);
            this.selected.forEach(id => fd.append('ids[]', id));
            fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            const r = await fetch('<?= base_url('admin/bulk') ?>/' + this.table, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
            const j = await r.json();
            if (j.ok) location.reload();
            else alert(j.error || 'Failed');
        },
        exportCsv() {
            if (! this.selected.length) return;
            location.href = '<?= base_url('admin/bulk') ?>/' + this.table + '/export?ids=' + this.selected.join(',');
        }
    }
}
</script>
<?php endif; ?>
