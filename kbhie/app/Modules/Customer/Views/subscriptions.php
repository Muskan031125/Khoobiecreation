<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-6 sm:py-10 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-4xl px-3 sm:px-4 lg:px-6">
        <?= view('App\Modules\Customer\Views\_account_nav') ?>

        <span class="eyebrow text-violet-600">🔄 Recurring</span>
        <h1 class="h-display text-2xl sm:text-3xl mt-1">My Subscriptions</h1>
        <p class="text-sm text-slate-500 mt-1">Active memberships and recurring classes.</p>

        <?php if (session('success')): ?>
            <div class="mt-3 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= esc(session('success')) ?></div>
        <?php endif; ?>

        <?php if (empty($rows)): ?>
            <div class="mt-6 bg-white rounded-2xl p-8 text-center">
                <div class="text-5xl">🔄</div>
                <h2 class="mt-3 font-display font-black text-lg">No active subscriptions</h2>
                <p class="mt-1 text-sm text-slate-500">Join Khoobie Insider or enrol in a monthly tuition to see them here.</p>
                <a href="<?= base_url('shop?type=membership') ?>" class="mt-4 inline-block btn-primary">Browse memberships →</a>
            </div>
        <?php else: ?>
            <div class="mt-5 space-y-3">
                <?php foreach ($rows as $s): ?>
                    <div class="bg-white rounded-2xl shadow-soft p-5 flex flex-wrap gap-4 items-start">
                        <?php if ($s['hero_image']): ?>
                            <img src="<?= esc($s['hero_image']) ?>" class="w-20 h-20 rounded-lg object-cover shrink-0">
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-display font-black"><?= esc($s['product_name'] ?? $s['plan_name'] ?? 'Subscription') ?></h3>
                                <span class="px-2 py-0.5 rounded text-xs font-bold capitalize <?= $s['status']==='active'?'bg-emerald-100 text-emerald-700':($s['status']==='paused'?'bg-amber-100 text-amber-700':'bg-slate-100 text-slate-700') ?>"><?= esc($s['status']) ?></span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                <?= esc($s['plan_name'] ?? '') ?> ·
                                <?= $s['plan_amount'] ? kb_money((int) $s['plan_amount']) : '—' ?> /
                                <?= esc($s['billing_cycle'] ?? 'cycle') ?>
                            </div>
                            <?php if (! empty($s['next_billing_at']) && $s['status'] === 'active'): ?>
                                <div class="mt-1 text-xs text-slate-600">📅 Next bill: <strong><?= kb_date($s['next_billing_at']) ?></strong></div>
                            <?php endif; ?>
                            <div class="mt-3 flex gap-2 flex-wrap">
                                <?php if ($s['status'] === 'active'): ?>
                                    <form method="post" action="<?= base_url('account/subscriptions/' . $s['id'] . '/pause') ?>"><?= csrf_field() ?><button class="text-xs font-bold text-amber-700 hover:underline">⏸ Pause</button></form>
                                    <form method="post" action="<?= base_url('account/subscriptions/' . $s['id'] . '/cancel') ?>" onsubmit="return confirm('Cancel this subscription?')"><?= csrf_field() ?><button class="text-xs font-bold text-rose-600 hover:underline">✕ Cancel</button></form>
                                <?php elseif ($s['status'] === 'paused'): ?>
                                    <form method="post" action="<?= base_url('account/subscriptions/' . $s['id'] . '/resume') ?>"><?= csrf_field() ?><button class="text-xs font-bold text-emerald-700 hover:underline">▶ Resume</button></form>
                                    <form method="post" action="<?= base_url('account/subscriptions/' . $s['id'] . '/cancel') ?>" onsubmit="return confirm('Cancel this subscription?')"><?= csrf_field() ?><button class="text-xs font-bold text-rose-600 hover:underline">✕ Cancel</button></form>
                                <?php endif; ?>
                                <?php if ($s['product_slug']): ?>
                                    <a href="<?= base_url('product/' . $s['product_slug']) ?>" class="text-xs font-bold text-brand-600 hover:underline">View product →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
