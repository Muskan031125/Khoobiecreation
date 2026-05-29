<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<form method="post" action="<?= base_url('admin/products' . ($product ? '/' . $product['id'] : '')) ?>" class="grid lg:grid-cols-[1fr_320px] gap-6" x-data="aiAssist({ productId: <?= (int)($product['id'] ?? 0) ?> })">
    <?= csrf_field() ?>
    <?php if ($product): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <div class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="font-bold">Basics</h2>
                <a href="<?= base_url('admin/import') ?>" class="text-xs font-bold text-brand-600 hover:underline">🤖 Import from URL →</a>
            </div>
            <input name="name" required placeholder="Product name" x-ref="name" value="<?= esc(old('name', $product['name'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
            <div class="grid sm:grid-cols-2 gap-3">
                <input name="sku" required placeholder="SKU" value="<?= esc(old('sku', $product['sku'] ?? '')) ?>" class="px-3 py-2 rounded-lg border border-slate-200">
                <input name="slug" placeholder="URL slug (auto from name)" value="<?= esc(old('slug', $product['slug'] ?? '')) ?>" class="px-3 py-2 rounded-lg border border-slate-200">
            </div>

            <!-- Short description with AI generate button -->
            <div class="relative">
                <textarea name="short_desc" rows="2" x-ref="shortDesc" placeholder="Short description (1-2 sentences)" class="w-full px-3 py-2 rounded-lg border border-slate-200 pr-28"><?= esc(old('short_desc', $product['short_desc'] ?? '')) ?></textarea>
                <button type="button" @click="genDesc(80)" :disabled="busy.desc"
                        class="absolute top-2 right-2 px-2.5 py-1 rounded-md bg-violet-100 hover:bg-violet-200 text-violet-700 text-[11px] font-bold disabled:opacity-50">
                    <span x-show="!busy.desc">✨ AI write</span>
                    <span x-show="busy.desc" x-cloak>…</span>
                </button>
            </div>

            <!-- Long description with AI generate button -->
            <div class="relative">
                <textarea name="long_desc" rows="6" x-ref="longDesc" placeholder="Long description / story" class="w-full px-3 py-2 rounded-lg border border-slate-200 pr-28"><?= esc(old('long_desc', $product['long_desc'] ?? '')) ?></textarea>
                <button type="button" @click="genDesc(200)" :disabled="busy.desc"
                        class="absolute top-2 right-2 px-2.5 py-1 rounded-md bg-violet-100 hover:bg-violet-200 text-violet-700 text-[11px] font-bold disabled:opacity-50">
                    <span x-show="!busy.desc">✨ AI write long</span>
                    <span x-show="busy.desc" x-cloak>…</span>
                </button>
            </div>

            <!-- Review summary block (if product has reviews) -->
            <?php if (! empty($product['id'])): ?>
                <div class="border-t border-slate-100 pt-3" x-data="{ summary: '', loading: false }">
                    <button type="button" @click="loading = true; fetch('<?= base_url('admin/ai/review-summary') ?>', { method:'POST', body: new URLSearchParams({product_id: <?= (int)$product['id'] ?>, '<?= csrf_token() ?>': '<?= csrf_hash() ?>'}), headers: {'Accept':'application/json'} }).then(r=>r.json()).then(j=>{ loading=false; if(j.ok) summary = j.summary; else summary = '(' + j.error + ')'; })"
                            class="text-xs font-bold text-violet-700 hover:underline">
                        ✨ AI: Summarise reviews
                    </button>
                    <div x-show="loading" x-cloak class="text-xs text-slate-500 mt-1">Reading reviews…</div>
                    <div x-show="summary" x-cloak class="mt-2 px-3 py-2 rounded-md bg-violet-50 border border-violet-200 text-xs text-slate-700" x-text="summary"></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">Pricing</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Selling price (₹)</label>
                    <input name="price" type="number" step="0.01" required value="<?= esc(old('price', $variant ? number_format($variant['price'] / 100, 2, '.', '') : '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                </div>
                <div>
                    <label class="text-xs text-slate-500">MRP / strike-through (₹)</label>
                    <input name="compare_at" type="number" step="0.01" value="<?= esc(old('compare_at', $variant && $variant['compare_at_price'] ? number_format($variant['compare_at_price'] / 100, 2, '.', '') : '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                </div>
                <div>
                    <label class="text-xs text-slate-500">HSN code</label>
                    <input name="hsn_code" value="<?= esc(old('hsn_code', $product['hsn_code'] ?? '9503')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                </div>
                <div>
                    <label class="text-xs text-slate-500">GST class</label>
                    <select name="tax_class_id" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                        <option value="">— select —</option>
                        <?php foreach ($taxClasses as $tc): ?>
                            <option value="<?= $tc['id'] ?>" <?= ($product['tax_class_id'] ?? '') == $tc['id'] ? 'selected' : '' ?>><?= esc($tc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">Audience</h2>
            <div class="grid sm:grid-cols-3 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Age min</label>
                    <input name="age_min_years" type="number" min="0" max="18" value="<?= esc(old('age_min_years', $product['age_min_years'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Age max</label>
                    <input name="age_max_years" type="number" min="0" max="18" value="<?= esc(old('age_max_years', $product['age_max_years'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Hero image (path)</label>
                    <input name="hero_image" value="<?= esc(old('hero_image', $product['hero_image'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">SEO</h2>
            <input name="seo_title" placeholder="SEO title" value="<?= esc(old('seo_title', $product['seo_title'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
            <textarea name="seo_description" rows="2" placeholder="SEO meta description (≤160 chars)" class="w-full px-3 py-2 rounded-lg border border-slate-200"><?= esc(old('seo_description', $product['seo_description'] ?? '')) ?></textarea>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">Publish</h2>
            <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                <?php foreach (['draft','active','out_of_stock','discontinued'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($product['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                <?php foreach (['simple','variable','bundle','digital','event','subscription'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($product['type'] ?? 'simple') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>>
                Featured (show on homepage)
            </label>
            <button class="w-full btn-primary">Save product</button>
            <?php if ($product): ?>
                <a href="<?= base_url('product/' . $product['slug']) ?>" target="_blank" class="block text-center text-xs text-slate-500 hover:underline">View on storefront &rarr;</a>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-2">
            <h2 class="font-bold">Categories</h2>
            <?php foreach ($categories as $c): ?>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="categories[]" value="<?= $c['id'] ?>" <?= in_array($c['id'], $selectedCats) ? 'checked' : '' ?>>
                    <?= esc($c['name']) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <?php if (! empty($partners)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-2">
            <h2 class="font-bold">Partner / Vendor</h2>
            <select name="partner_id" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                <option value="">— Khoobie in-house —</option>
                <?php foreach ($partners as $pn): ?>
                    <option value="<?= $pn['id'] ?>" <?= ($product['partner_id'] ?? '') == $pn['id'] ? 'selected' : '' ?>><?= esc($pn['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </aside>
</form>

<!-- SEO meta block — sits below main form, also AI-assisted -->
<?php if (! empty($product)): ?>
<div class="mt-6 max-w-5xl bg-white rounded-2xl shadow-sm p-5" x-data="seoAi({ productId: <?= (int)$product['id'] ?> })">
    <div class="flex items-center justify-between">
        <h2 class="font-bold">SEO meta tags</h2>
        <button type="button" @click="generate()" :disabled="busy" class="px-3 py-1.5 rounded-md bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-bold disabled:opacity-50">
            <span x-show="!busy">✨ Generate with AI</span><span x-show="busy" x-cloak>Generating…</span>
        </button>
    </div>
    <form method="post" action="<?= base_url('admin/products/' . $product['id'] . '/seo') ?>" class="mt-3 space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <div>
            <label class="text-xs text-slate-500">SEO title <span class="text-slate-400">(≤60 chars)</span></label>
            <input name="seo_title" x-model="title" maxlength="80" value="<?= esc($product['seo_title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
        </div>
        <div>
            <label class="text-xs text-slate-500">Meta description <span class="text-slate-400">(≤160 chars)</span></label>
            <textarea name="seo_description" x-model="desc" rows="3" maxlength="200" class="w-full px-3 py-2 rounded-lg border border-slate-200"><?= esc($product['seo_description'] ?? '') ?></textarea>
        </div>
    </form>
</div>
<?php endif; ?>

<script>
function aiAssist(opts) {
    return {
        productId: opts.productId,
        busy: { desc: false, alt: false },
        async genDesc(words) {
            this.busy.desc = true;
            try {
                const fd = new FormData();
                fd.append('product_id', this.productId);
                fd.append('name', this.$refs.name.value);
                fd.append('short_desc', this.$refs.shortDesc.value);
                fd.append('words', words);
                fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                const r = await fetch('<?= base_url('admin/ai/description') ?>', { method: 'POST', body: fd, headers: {'Accept':'application/json'} });
                const j = await r.json();
                if (j.ok && j.text) {
                    if (words <= 100) this.$refs.shortDesc.value = j.text;
                    else              this.$refs.longDesc.value  = j.text;
                } else alert('AI: ' + (j.error || 'failed'));
            } catch (e) { alert('Network error'); }
            this.busy.desc = false;
        }
    }
}
function seoAi(opts) {
    return {
        productId: opts.productId, busy: false, title: '', desc: '',
        async generate() {
            this.busy = true;
            try {
                const fd = new FormData();
                fd.append('product_id', this.productId);
                fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                const r = await fetch('<?= base_url('admin/ai/seo-meta') ?>', { method: 'POST', body: fd, headers: {'Accept':'application/json'} });
                const j = await r.json();
                if (j.ok) { this.title = j.title || this.title; this.desc = j.description || this.desc; }
            } catch (e) { alert('Network error'); }
            this.busy = false;
        }
    }
}
</script>

<?= $this->endSection() ?>
