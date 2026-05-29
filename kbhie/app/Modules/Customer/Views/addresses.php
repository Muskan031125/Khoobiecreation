<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-6 sm:py-10 bg-slate-50 min-h-[60vh]" x-data="{ editing: null, showForm: false }">
    <div class="mx-auto max-w-4xl px-3 sm:px-4 lg:px-6">
        <?= view('App\Modules\Customer\Views\_account_nav') ?>

        <div class="flex items-end justify-between flex-wrap gap-3">
            <div>
                <span class="eyebrow text-emerald-600">📍 Delivery</span>
                <h1 class="h-display text-2xl sm:text-3xl mt-1">Saved addresses</h1>
            </div>
            <button @click="showForm = true; editing = null"
                    class="px-4 py-2 rounded-full bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold transition">+ Add address</button>
        </div>

        <?php if (session('success')): ?>
            <div class="mt-3 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <!-- Address list -->
        <div class="mt-5 grid sm:grid-cols-2 gap-3">
            <?php foreach ($rows as $a): ?>
                <div class="bg-white rounded-2xl shadow-soft p-4 relative <?= $a['is_default'] ? 'ring-2 ring-brand-400' : '' ?>">
                    <?php if ($a['is_default']): ?>
                        <span class="absolute top-3 right-3 px-2 py-0.5 rounded-full bg-brand-500 text-white text-[10px] font-black uppercase tracking-wider">Default</span>
                    <?php endif; ?>
                    <div class="font-bold text-sm"><?= esc($a['label']) ?> · <?= esc($a['name']) ?></div>
                    <div class="text-xs text-slate-500 mt-0.5"><?= esc($a['phone']) ?></div>
                    <div class="mt-2 text-sm text-slate-700 leading-snug">
                        <?= esc($a['line1']) ?><?= $a['line2'] ? ', ' . esc($a['line2']) : '' ?><br>
                        <?= esc($a['city']) ?>, <?= esc($a['state']) ?> — <?= esc($a['pincode']) ?>
                    </div>
                    <div class="mt-3 flex gap-2 text-xs">
                        <button @click='editing = <?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>; showForm = true'
                                class="text-brand-600 font-bold hover:underline">Edit</button>
                        <form method="post" action="<?= base_url('account/addresses/' . $a['id'] . '/delete') ?>" onsubmit="return confirm('Remove this address?')" class="inline">
                            <?= csrf_field() ?>
                            <button class="text-rose-600 font-bold hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <div class="sm:col-span-2 bg-white rounded-2xl p-8 text-center">
                    <div class="text-5xl">📍</div>
                    <h2 class="mt-3 font-display font-black text-lg">No saved addresses yet</h2>
                    <p class="mt-1 text-sm text-slate-500">Save one to speed up checkout next time.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add/edit modal -->
        <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3">
            <div @click="showForm = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-2xl shadow-soft-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white px-5 py-4 border-b border-slate-100 flex justify-between">
                    <h3 class="font-display font-black" x-text="editing ? 'Edit address' : 'New address'"></h3>
                    <button @click="showForm = false" class="text-2xl leading-none text-slate-400">&times;</button>
                </div>
                <form method="post" action="<?= base_url('account/addresses/save') ?>" class="p-5 space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" :value="editing?.id || ''">
                    <input name="label" placeholder="Label · Home / Office (optional)" :value="editing?.label || ''" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                    <input name="name" required placeholder="Full name *" :value="editing?.name || ''" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                    <input name="phone" required type="tel" placeholder="Phone *" maxlength="10" :value="editing?.phone || ''" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                    <input name="line1" required placeholder="Address line 1 *" :value="editing?.line1 || ''" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                    <input name="line2" placeholder="Address line 2 (optional)" :value="editing?.line2 || ''" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                    <input name="landmark" placeholder="Landmark (optional)" :value="editing?.landmark || ''" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200">
                    <div class="grid grid-cols-2 gap-2">
                        <input name="city" required placeholder="City *" :value="editing?.city || ''" class="px-3 py-2 rounded-lg border-2 border-slate-200">
                        <input name="state" required placeholder="State *" :value="editing?.state || ''" class="px-3 py-2 rounded-lg border-2 border-slate-200">
                    </div>
                    <input name="pincode" required placeholder="Pincode *" maxlength="6" :value="editing?.pincode || ''" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200" inputmode="numeric">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_default" value="1" :checked="editing?.is_default">
                        Set as default
                    </label>
                    <button type="submit" class="w-full btn-primary">Save address</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
