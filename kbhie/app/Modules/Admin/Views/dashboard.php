<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$money = fn ($p) => '₹' . number_format(round($p / 100));
$maxSpark = max(array_column($sparkline, 'revenue')) ?: 1;
?>

<div class="space-y-6">

    <!-- ===== Hero KPIs ===== -->
    <div>
        <h1 class="text-2xl font-black">Dashboard</h1>
        <p class="text-sm text-slate-500">Live data · refreshes on every load</p>
    </div>

    <!-- Revenue grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ([
            ['Today',      $rev['today'],    'brand'],
            ['This week',  $rev['week'],     'sky'],
            ['This month', $rev['month'],    'violet'],
            ['Lifetime',   $rev['lifetime'], 'emerald'],
        ] as [$label, $val, $color]): ?>
            <div class="bg-white rounded-2xl p-5 shadow-sm">
                <div class="text-[10px] uppercase tracking-wider font-bold text-slate-500"><?= $label ?> revenue</div>
                <div class="mt-1 text-2xl lg:text-3xl font-display font-black text-<?= $color ?>-600 tabular-nums"><?= $money($val) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick counts -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="<?= base_url('admin/orders') ?>" class="bg-amber-50 ring-1 ring-amber-200 hover:ring-amber-400 rounded-xl p-3 transition">
            <div class="text-xs font-bold text-amber-700">⏳ Pending orders</div>
            <div class="mt-1 text-xl font-black text-amber-900 tabular-nums"><?= $counts['orders_pending'] ?></div>
        </a>
        <a href="<?= base_url('admin/products') ?>" class="bg-slate-50 ring-1 ring-slate-200 hover:ring-slate-400 rounded-xl p-3 transition">
            <div class="text-xs font-bold text-slate-700">📦 Active products</div>
            <div class="mt-1 text-xl font-black text-slate-900 tabular-nums"><?= $counts['products_active'] ?></div>
        </a>
        <a href="<?= base_url('admin/customers') ?>" class="bg-sky-50 ring-1 ring-sky-200 hover:ring-sky-400 rounded-xl p-3 transition">
            <div class="text-xs font-bold text-sky-700">👥 Customers</div>
            <div class="mt-1 text-xl font-black text-sky-900 tabular-nums"><?= number_format($counts['customers']) ?></div>
        </a>
        <a href="<?= base_url('admin/blogs') ?>" class="bg-violet-50 ring-1 ring-violet-200 hover:ring-violet-400 rounded-xl p-3 transition">
            <div class="text-xs font-bold text-violet-700">📝 Blog posts live</div>
            <div class="mt-1 text-xl font-black text-violet-900 tabular-nums"><?= $counts['blog_published'] ?></div>
        </a>
    </div>

    <!-- 7-day sparkline + intent funnel side by side -->
    <div class="grid lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <div class="flex items-end justify-between mb-3">
                <h2 class="font-bold">Last 7 days · revenue</h2>
                <a href="<?= base_url('admin/reports') ?>" class="text-xs text-brand-600 font-bold hover:underline">Full reports →</a>
            </div>
            <div class="flex items-end justify-between h-32 gap-2">
                <?php foreach ($sparkline as $d): $h = $maxSpark > 0 ? round(($d['revenue'] / $maxSpark) * 100) : 0; ?>
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="text-[10px] font-mono text-slate-500"><?= $d['revenue'] > 0 ? $money($d['revenue']) : '—' ?></div>
                        <div class="w-full rounded-t bg-gradient-to-t from-brand-500 to-bloom-400" style="height: <?= max(2, $h) ?>%"></div>
                        <div class="text-[10px] font-bold text-slate-600"><?= $d['day'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h2 class="font-bold mb-3">Intent funnel · last 30 days</h2>
            <?php
            $max = max(1, $intentFunnel['captured']);
            $stages = [
                ['Captured',  $intentFunnel['captured'],  'bg-slate-500'],
                ['OTP verified', $intentFunnel['verified'], 'bg-sky-500'],
                ['Seat reserved', $intentFunnel['reserved'], 'bg-amber-500'],
                ['Converted', $intentFunnel['converted'], 'bg-emerald-500'],
            ];
            ?>
            <div class="space-y-2">
                <?php foreach ($stages as [$label, $n, $color]):
                    $w = round(($n / $max) * 100);
                ?>
                    <div>
                        <div class="flex justify-between text-xs mb-0.5"><span class="font-bold"><?= $label ?></span><span class="tabular-nums text-slate-500"><?= $n ?></span></div>
                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full <?= $color ?>" style="width: <?= $w ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="<?= base_url('admin/leads') ?>" class="block mt-4 text-center text-xs font-bold text-brand-600 hover:underline">→ View lead inbox</a>
        </div>
    </div>

    <!-- Order status mix + Sources + Referrals -->
    <div class="grid lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h2 class="font-bold mb-3">Orders by status</h2>
            <ul class="space-y-1.5 text-sm">
                <?php foreach ($statusBreakdown as $s): ?>
                    <li class="flex justify-between">
                        <span class="capitalize"><?= esc(str_replace('_', ' ', $s['status'])) ?></span>
                        <span class="font-bold tabular-nums"><?= (int) $s['n'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h2 class="font-bold mb-3">Traffic sources · this month</h2>
            <?php if (empty($sources)): ?>
                <p class="text-xs text-slate-500">No order attribution data yet — UTM filter is now live; data appears with new orders.</p>
            <?php else: ?>
                <ul class="space-y-1.5 text-sm">
                    <?php foreach ($sources as $s): ?>
                        <li class="flex justify-between">
                            <span class="capitalize"><?= esc($s['s']) ?></span>
                            <span class="font-bold tabular-nums"><?= (int) $s['n'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h2 class="font-bold mb-3">Referrals · this month</h2>
            <div class="space-y-2">
                <div class="flex justify-between text-sm"><span>Sign-ups via referral</span><span class="font-bold tabular-nums"><?= $refStats['signups'] ?></span></div>
                <div class="flex justify-between text-sm"><span>First-order conversions</span><span class="font-bold tabular-nums text-emerald-600"><?= $refStats['rewarded'] ?></span></div>
                <div class="flex justify-between text-sm"><span>Points awarded</span><span class="font-bold tabular-nums text-amber-600">★ <?= $refStats['points'] ?></span></div>
            </div>
        </div>
    </div>

    <!-- Top products + Low stock + Recent leads -->
    <div class="grid lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm lg:col-span-1">
            <h2 class="font-bold mb-3">🏆 Top products · this month</h2>
            <?php if (empty($topProducts)): ?>
                <p class="text-xs text-slate-500">No sales this month yet.</p>
            <?php else: ?>
                <ol class="space-y-2 text-sm">
                    <?php foreach ($topProducts as $i => $p): ?>
                        <li class="flex items-start gap-2">
                            <span class="text-xs text-slate-400 tabular-nums w-4"><?= $i + 1 ?>.</span>
                            <div class="flex-1 min-w-0">
                                <a href="<?= base_url('product/' . $p['slug']) ?>" target="_blank" class="font-semibold line-clamp-1 hover:text-brand-600"><?= esc($p['name']) ?></a>
                                <div class="text-xs text-slate-500"><?= (int) $p['units'] ?> units · <?= $money((int) $p['revenue']) ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm lg:col-span-1">
            <h2 class="font-bold mb-3">⚠ Low stock</h2>
            <?php if (empty($lowStock)): ?>
                <p class="text-xs text-emerald-700">✓ All products well-stocked.</p>
            <?php else: ?>
                <ol class="space-y-1.5 text-sm">
                    <?php foreach ($lowStock as $p): ?>
                        <li class="flex items-center justify-between">
                            <a href="<?= base_url('product/' . $p['slug']) ?>" target="_blank" class="font-semibold line-clamp-1 hover:text-brand-600"><?= esc($p['name']) ?></a>
                            <span class="text-xs font-black <?= $p['qty_on_hand'] <= 0 ? 'text-rose-600' : 'text-amber-600' ?>"><?= (int) $p['qty_on_hand'] ?> left</span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm lg:col-span-1">
            <h2 class="font-bold mb-3">📥 Lead inbox</h2>
            <?php if (empty($recentLeads)): ?>
                <p class="text-xs text-slate-500">No open leads. Nice!</p>
            <?php else: ?>
                <ol class="space-y-2 text-sm">
                    <?php foreach ($recentLeads as $l): ?>
                        <li class="border-b border-slate-100 pb-2 last:border-0">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 font-bold uppercase tracking-wider"><?= esc($l['kind']) ?></span>
                                <span class="text-slate-500"><?= date('j M, g:i A', strtotime($l['created_at'])) ?></span>
                            </div>
                            <div class="mt-0.5 font-semibold"><?= esc($l['name'] ?: '—') ?> · <?= esc($l['phone'] ?: $l['email']) ?></div>
                            <?php if ($l['product_name']): ?>
                                <a href="<?= base_url('product/' . $l['slug']) ?>" target="_blank" class="text-xs text-brand-600 hover:underline line-clamp-1"><?= esc($l['product_name']) ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <a href="<?= base_url('admin/leads') ?>" class="block mt-3 text-center text-xs font-bold text-brand-600 hover:underline">View all leads →</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="bg-gradient-to-br from-violet-50 to-rose-50 rounded-2xl p-5">
        <h2 class="font-bold">⚡ Quick actions</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="<?= base_url('admin/import') ?>" class="px-4 py-2 rounded-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold transition">🤖 Import product from URL</a>
            <a href="<?= base_url('admin/blogs/new') ?>" class="px-4 py-2 rounded-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold transition">✨ AI-draft a blog post</a>
            <a href="<?= base_url('admin/products/new') ?>" class="px-4 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold transition">+ New product</a>
            <a href="<?= base_url('admin/coupons/new') ?>" class="px-4 py-2 rounded-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold transition">+ New coupon</a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
