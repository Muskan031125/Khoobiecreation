<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div x-data="urlImporter()" class="space-y-6 max-w-5xl">

    <div>
        <h1 class="text-2xl font-black">Import from URL</h1>
        <p class="text-sm text-slate-500 mt-1">Paste any Amazon / Flipkart / brand-site product URL — AI extracts the data, you review &amp; save.</p>
    </div>

    <!-- ===== URL input ===== -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Source URL</label>
        <div class="mt-2 flex gap-2">
            <input type="url" x-model="url"
                   placeholder="https://www.amazon.in/dp/B07XJ8C8YN  (or any product page)"
                   class="flex-1 px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none">
            <button @click="fetchUrl()" :disabled="busy"
                    class="px-5 py-3 rounded-lg bg-slate-900 hover:bg-brand-500 text-white font-bold text-sm uppercase tracking-wider transition disabled:opacity-50">
                <span x-show="!busy">⚡ Fetch &amp; extract</span>
                <span x-show="busy" x-cloak>🤖 LLM thinking…</span>
            </button>
        </div>
        <p class="mt-2 text-[11px] text-slate-500">First request can take 10-15 seconds while the LLM extracts. We never publish automatically — review the draft below.</p>
        <div x-show="error" x-cloak class="mt-3 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm" x-text="error"></div>
    </div>

    <!-- ===== Draft preview ===== -->
    <div x-show="draft" x-cloak class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-black">Review the AI-extracted draft</h2>
            <span class="text-xs text-emerald-700 font-bold">✓ AI extraction complete</span>
        </div>

        <div class="grid lg:grid-cols-[200px_1fr] gap-5">
            <div>
                <img :src="draft.hero_image" class="w-full aspect-square object-cover rounded-lg ring-1 ring-slate-200">
                <div x-show="draft.gallery && draft.gallery.length" class="mt-2 grid grid-cols-4 gap-1">
                    <template x-for="img in (draft.gallery || []).slice(0,4)">
                        <img :src="img" class="aspect-square object-cover rounded">
                    </template>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Name</label>
                    <input type="text" x-model="draft.name" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Short description</label>
                    <textarea x-model="draft.short_desc" rows="2" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Price (paise)</label>
                        <input type="number" x-model.number="draft.price_paise" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                        <div class="text-[10px] text-slate-500 mt-0.5">₹<span x-text="(draft.price_paise/100).toFixed(0)"></span></div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">MRP (paise)</label>
                        <input type="number" x-model.number="draft.compare_at_paise" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                        <div class="text-[10px] text-slate-500 mt-0.5">₹<span x-text="((draft.compare_at_paise||0)/100).toFixed(0)"></span></div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Age min</label>
                        <input type="number" x-model.number="draft.age_min_years" min="0" max="18" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Age max</label>
                        <input type="number" x-model.number="draft.age_max_years" min="0" max="18" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Brand (from source)</label>
                        <input type="text" x-model="draft.source_brand" readonly class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 bg-slate-50 text-slate-500">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Category</label>
                    <select x-model="categoryId" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                        <option value="">— Pick a category —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= empty($c['parent_id']) ? 'class="font-bold"' : '' ?>>
                                <?= empty($c['parent_id']) ? '' : '— ' ?><?= esc($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div x-show="draft.bullets && draft.bullets.length">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Key bullets (AI-extracted)</label>
                    <ul class="mt-1 text-sm text-slate-700 list-disc ml-5 space-y-0.5">
                        <template x-for="b in draft.bullets"><li x-text="b"></li></template>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
            <p class="text-xs text-slate-500">SKU: <span class="font-mono font-bold" x-text="draft.sku"></span> · Status: <strong>Draft (not published)</strong></p>
            <div class="flex gap-2">
                <button @click="draft = null; categoryId = ''" class="px-4 py-2 rounded-lg border-2 border-slate-200 hover:bg-slate-50 text-sm font-bold">Discard</button>
                <button @click="save()" :disabled="busy"
                        class="px-5 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold uppercase tracking-wider shadow-cta transition disabled:opacity-50">
                    <span x-show="!busy">💾 Save as draft</span>
                    <span x-show="busy" x-cloak>Saving…</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ===== Recent imports ===== -->
    <?php if (! empty($recent)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h2 class="text-lg font-black">Recently imported</h2>
            <table class="w-full mt-3 text-sm">
                <thead class="text-xs uppercase tracking-wider text-slate-500 border-b">
                    <tr><th class="text-left py-2"></th><th class="text-left py-2">Product</th><th class="text-left py-2">SKU</th><th class="text-left py-2">Status</th><th class="text-left py-2">Imported</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr class="border-b last:border-0">
                            <td class="py-2 w-12"><img src="<?= esc($r['hero_image']) ?>" class="w-10 h-10 rounded object-cover" alt=""></td>
                            <td class="py-2 font-semibold"><?= esc($r['name']) ?></td>
                            <td class="py-2 font-mono text-xs text-slate-500"><?= esc($r['sku']) ?></td>
                            <td class="py-2"><span class="px-2 py-0.5 rounded text-xs font-bold <?= $r['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>"><?= esc($r['status']) ?></span></td>
                            <td class="py-2 text-xs text-slate-500"><?= date('j M, g:i A', strtotime($r['created_at'])) ?></td>
                            <td class="py-2 text-right">
                                <a href="<?= base_url('admin/products/' . $r['id'] . '/edit') ?>" class="text-xs text-brand-600 font-bold hover:underline">Edit →</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function urlImporter() {
    return {
        url: '',
        draft: null,
        categoryId: '',
        busy: false,
        error: '',
        async fetchUrl() {
            this.error = ''; this.draft = null; this.busy = true;
            try {
                const fd = new FormData();
                fd.append('url', this.url);
                fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                const r = await fetch('<?= base_url('admin/import/fetch') ?>', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.ok) { this.draft = j.draft; this.categoryId = j.draft.category_id_guess || ''; }
                else { this.error = j.error || 'Could not extract.'; }
            } catch (e) { this.error = 'Network error: ' + e.message; }
            this.busy = false;
        },
        async save() {
            this.error = ''; this.busy = true;
            try {
                const fd = new FormData();
                fd.append('draft', JSON.stringify(this.draft));
                fd.append('category_id', this.categoryId);
                fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                const r = await fetch('<?= base_url('admin/import/save') ?>', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.ok) location.href = '<?= base_url('admin/products') ?>/' + j.product_id + '/edit';
                else this.error = j.error || 'Save failed.';
            } catch (e) { this.error = 'Network error.'; }
            this.busy = false;
        }
    }
}
</script>

<?= $this->endSection() ?>
