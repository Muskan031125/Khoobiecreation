<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-6 sm:py-10 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-4xl px-3 sm:px-4 lg:px-6">

        <?= view('App\Modules\Customer\Views\_account_nav') ?>

        <!-- Hero: tier + points -->
        <div class="rounded-3xl bg-gradient-to-br from-amber-400 via-rose-500 to-violet-600 p-6 sm:p-8 text-white shadow-cta-lg">
            <span class="eyebrow text-white/90">⭐ Khoobie Wallet</span>
            <div class="mt-1 flex items-end justify-between flex-wrap gap-3">
                <div>
                    <div class="text-5xl font-display font-black tabular-nums">★ <?= number_format((int) $loyalty['points_balance']) ?></div>
                    <div class="text-xs uppercase tracking-wider font-bold opacity-90 mt-1">Points balance · worth ₹<?= number_format((int) ($loyalty['points_balance'] / 2)) ?></div>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase tracking-wider font-bold opacity-80">Your tier</div>
                    <div class="font-display text-2xl font-black mt-1 capitalize"><?= esc($loyalty['tier'] ?? 'bronze') ?></div>
                </div>
            </div>
        </div>

        <!-- Earn more -->
        <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="<?= base_url('account/referrals') ?>" class="bg-white rounded-2xl p-4 shadow-soft hover:shadow-soft-lg transition">
                <div class="text-2xl">🎁</div>
                <div class="mt-1 font-bold text-sm">Refer friends</div>
                <div class="text-[11px] text-slate-500">Earn 200 pts per signup</div>
            </a>
            <a href="<?= base_url('shop') ?>" class="bg-white rounded-2xl p-4 shadow-soft hover:shadow-soft-lg transition">
                <div class="text-2xl">🛒</div>
                <div class="mt-1 font-bold text-sm">Shop</div>
                <div class="text-[11px] text-slate-500">1 pt per ₹1 spent</div>
            </a>
            <a href="<?= base_url('account/orders') ?>" class="bg-white rounded-2xl p-4 shadow-soft hover:shadow-soft-lg transition">
                <div class="text-2xl">⭐</div>
                <div class="mt-1 font-bold text-sm">Review products</div>
                <div class="text-[11px] text-slate-500">50 pts per verified review</div>
            </a>
            <a href="<?= base_url('shop?type=membership') ?>" class="bg-white rounded-2xl p-4 shadow-soft hover:shadow-soft-lg transition">
                <div class="text-2xl">👑</div>
                <div class="mt-1 font-bold text-sm">Khoobie Insider</div>
                <div class="text-[11px] text-slate-500">Bonus points + perks</div>
            </a>
        </div>

        <!-- Transaction history -->
        <div class="mt-4 bg-white rounded-2xl shadow-soft p-5">
            <h2 class="font-display font-black text-lg">Transaction history</h2>
            <?php if (empty($txns)): ?>
                <p class="mt-3 text-sm text-slate-500">No transactions yet. Earn your first points by shopping or referring a friend!</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-slate-100">
                    <?php foreach ($txns as $t): ?>
                        <li class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold capitalize"><?= esc(str_replace('_',' ', $t['reason'])) ?></div>
                                <div class="text-[10px] text-slate-500"><?= date('j M Y, g:i A', strtotime($t['created_at'])) ?></div>
                                <?php if (! empty($t['note'])): ?><div class="text-[11px] text-slate-600 mt-0.5"><?= esc($t['note']) ?></div><?php endif; ?>
                            </div>
                            <div class="text-sm font-black tabular-nums <?= $t['kind'] === 'earn' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                <?= $t['kind'] === 'earn' ? '+' : '−' ?><?= number_format(abs((int) $t['points'])) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
