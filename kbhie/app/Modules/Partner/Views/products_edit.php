<?= $this->extend('layouts/partner') ?>
<?= $this->section('content') ?>

<form method="post" action="<?= base_url('partner/products/save') ?>" class="max-w-4xl space-y-4">
    <?= csrf_field() ?>
    <?php if ($product): ?><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><?php endif; ?>

    <div class="flex items-end justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl font-black"><?= $product ? 'Edit product' : 'New product' ?></h1>
            <p class="text-sm text-slate-500">All submissions go to draft. Khoobie admin reviews + publishes within 24h.</p>
        </div>
        <a href="<?= base_url('partner/products') ?>" class="text-sm text-slate-500 hover:underline">← All products</a>
    </div>

    <?php if (session('errors')): ?>
        <ul class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm list-disc ml-5">
            <?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Basics -->
    <section class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
        <h2 class="font-bold">Basics</h2>
        <input name="name" required placeholder="Product name *" value="<?= esc(old('name', $product['name'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
        <div class="grid sm:grid-cols-2 gap-3">
            <input name="sku" placeholder="SKU (auto if blank)" value="<?= esc(old('sku', $product['sku'] ?? '')) ?>" class="px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
            <select name="type" class="px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
                <?php foreach (['simple'=>'Physical product','digital'=>'Digital download','course'=>'Online course','service'=>'1-on-1 service','meetup'=>'In-person event'] as $k=>$lbl): ?>
                    <option value="<?= $k ?>" <?= ($product['type'] ?? 'simple') === $k ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <textarea name="short_desc" rows="2" placeholder="Short description (1-2 sentences for the card)" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none"><?= esc(old('short_desc', $product['short_desc'] ?? '')) ?></textarea>
        <textarea name="long_desc" rows="5" placeholder="Long description / story for the PDP" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none"><?= esc(old('long_desc', $product['long_desc'] ?? '')) ?></textarea>
    </section>

    <!-- Pricing + stock -->
    <section class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
        <h2 class="font-bold">Pricing &amp; stock</h2>
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-slate-500">Selling price (₹) *</label>
                <input name="price_inr" type="number" min="1" step="1" required value="<?= esc(old('price_inr', $variant ? round($variant['price']/100) : '')) ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
            </div>
            <div>
                <label class="text-xs text-slate-500">MRP / strike-through (₹)</label>
                <input name="mrp_inr" type="number" min="0" step="1" value="<?= esc(old('mrp_inr', $variant && $variant['compare_at_price'] ? round($variant['compare_at_price']/100) : '')) ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
            </div>
            <div>
                <label class="text-xs text-slate-500">Stock quantity</label>
                <input name="stock_qty" type="number" min="0" step="1" value="0" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
            </div>
        </div>
    </section>

    <!-- Audience + media -->
    <section class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
        <h2 class="font-bold">Audience &amp; media</h2>
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-slate-500">Min age</label>
                <input name="age_min_years" type="number" min="0" max="18" value="<?= esc(old('age_min_years', $product['age_min_years'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
            </div>
            <div>
                <label class="text-xs text-slate-500">Max age</label>
                <input name="age_max_years" type="number" min="0" max="18" value="<?= esc(old('age_max_years', $product['age_max_years'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
            </div>
            <div>
                <label class="text-xs text-slate-500">Hero image URL</label>
                <input name="hero_image" placeholder="https://..." value="<?= esc(old('hero_image', $product['hero_image'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
            </div>
        </div>
        <p class="text-[11px] text-slate-500">For media uploads &amp; gallery, contact your Khoobie partner manager — coming to self-serve soon.</p>
    </section>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary">💾 Save as draft</button>
        <p class="text-xs text-slate-500">Admin reviews + publishes within 24h.</p>
    </div>
</form>

<?= $this->endSection() ?>
