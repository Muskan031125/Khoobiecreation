<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $money = fn ($p) => '₹' . number_format(round($p / 100)); ?>

<div class="space-y-6 max-w-7xl">

    <!-- Hero -->
    <div class="rounded-3xl bg-gradient-to-br from-brand-500 via-rose-500 to-violet-600 p-6 sm:p-10 text-white shadow-cta-lg">
        <span class="eyebrow text-white/80">📈 Investor view</span>
        <h1 class="h-display text-3xl sm:text-5xl mt-2">Khoobie · the screen-free marketplace for India</h1>
        <p class="mt-3 max-w-2xl text-white/90">Live numbers · refreshed on every visit. <?= number_format($stats['products_active']) ?> active products · <?= number_format($stats['classes_listed']) ?> classes · <?= number_format($stats['cities_served']) ?> cities · <?= number_format($stats['partners']) ?> partners.</p>

        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <?php foreach ([
                ['Today',      $stats['gmv_today']],
                ['Last 7d',    $stats['gmv_7d']],
                ['Last 30d',   $stats['gmv_30d']],
                ['Lifetime',   $stats['gmv_lifetime']],
            ] as [$label, $val]): ?>
                <div class="bg-white/15 backdrop-blur rounded-2xl p-3">
                    <div class="text-[10px] uppercase tracking-wider font-bold opacity-80"><?= $label ?> GMV</div>
                    <div class="mt-1 text-2xl font-display font-black tabular-nums"><?= $money($val) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick counts -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <?php foreach ([
            ['👥 Customers',     number_format($stats['customers'])],
            ['🛒 Orders',         number_format($stats['orders_lifetime'])],
            ['🤝 Partners',       number_format($stats['partners'])],
            ['🎓 Classes',        number_format($stats['classes_listed'])],
            ['📍 Cities',          number_format($stats['cities_served'])],
            ['📝 Blog posts',     number_format($stats['blog_published'])],
        ] as [$lbl, $val]): ?>
            <div class="bg-white rounded-2xl p-4 shadow-soft">
                <div class="text-xs text-slate-500 font-bold"><?= $lbl ?></div>
                <div class="mt-1 text-2xl font-display font-black tabular-nums"><?= $val ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 30-day revenue sparkline -->
    <div class="bg-white rounded-2xl shadow-soft p-5">
        <div class="flex items-end justify-between mb-3">
            <h2 class="font-display font-black text-lg">📈 30-day GMV</h2>
            <span class="text-xs text-slate-500">Avg ₹<?= number_format(round(array_sum(array_column($daily, 'gmv')) / count($daily) / 100)) ?> / day</span>
        </div>
        <div class="flex items-end justify-between h-32 gap-px">
            <?php foreach ($daily as $d):
                $h = $maxDaily > 0 ? round(($d['gmv'] / $maxDaily) * 100) : 0;
                $today = $d['date'] === date('Y-m-d');
            ?>
                <div class="flex-1 flex flex-col items-center" title="<?= $d['date'] ?>: <?= $money($d['gmv']) ?>">
                    <div class="w-full rounded-t <?= $today ? 'bg-amber-400' : 'bg-gradient-to-t from-brand-500 to-bloom-400' ?>" style="height: <?= max(2, $h) ?>%"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-2 flex justify-between text-[10px] text-slate-400">
            <span><?= date('j M', strtotime($daily[0]['date'])) ?></span>
            <span><?= date('j M', strtotime(end($daily)['date'])) ?> (today)</span>
        </div>
    </div>

    <!-- GMV mix + Funnel side by side -->
    <div class="grid lg:grid-cols-2 gap-4">
        <!-- GMV by product line -->
        <div class="bg-white rounded-2xl shadow-soft p-5">
            <h2 class="font-display font-black text-lg">💸 Revenue mix · last 90 days</h2>
            <div class="mt-4 space-y-3">
                <?php foreach ($gmvByLine as $g):
                    $pct = round(($g['rev'] / $totalRev) * 100);
                ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-bold"><?= esc($g['line']) ?></span>
                            <span class="text-slate-500"><?= $money((int) $g['rev']) ?> · <?= (int) $g['orders'] ?> orders · <?= $pct ?>%</span>
                        </div>
                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-brand-500 to-violet-500" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($gmvByLine)): ?>
                    <p class="text-sm text-slate-500">No paid orders in last 90 days yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Funnel -->
        <div class="bg-white rounded-2xl shadow-soft p-5">
            <h2 class="font-display font-black text-lg">🎯 Conversion funnel · last 30 days</h2>
            <?php
            $max = max(1, $funnel['visitors_est']);
            $stages = [
                ['Visitors (est)', $funnel['visitors_est'], 'bg-slate-400'],
                ['Lead captured',  $funnel['leads'],       'bg-sky-500'],
                ['OTP verified',   $funnel['verified'],    'bg-violet-500'],
                ['Order placed',   $funnel['orders'],      'bg-amber-500'],
                ['Paid order',     $funnel['paid'],        'bg-emerald-500'],
            ];
            ?>
            <div class="mt-4 space-y-2">
                <?php foreach ($stages as [$lbl, $n, $color]):
                    $w = round(($n / $max) * 100);
                ?>
                    <div>
                        <div class="flex justify-between text-xs mb-0.5"><span class="font-bold"><?= $lbl ?></span><span class="tabular-nums text-slate-500"><?= number_format($n) ?></span></div>
                        <div class="h-4 bg-slate-100 rounded-full overflow-hidden"><div class="h-full <?= $color ?>" style="width: <?= $w ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($funnel['visitors_est'] > 0): ?>
                <div class="mt-4 text-xs text-slate-500">
                    Lead → Paid conversion: <strong><?= $funnel['leads'] > 0 ? round(($funnel['paid'] / $funnel['leads']) * 100, 1) : 0 ?>%</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AI usage callout -->
    <div class="bg-gradient-to-br from-violet-100 via-rose-50 to-amber-50 rounded-2xl shadow-soft p-6">
        <span class="eyebrow text-violet-700">✨ AI everywhere</span>
        <h2 class="h-display text-2xl mt-1">AI is a coworker, not a feature</h2>
        <div class="mt-4 grid grid-cols-3 gap-3">
            <div class="bg-white rounded-xl p-4">
                <div class="text-3xl font-display font-black text-violet-700"><?= number_format($aiStats['blogs_ai']) ?></div>
                <div class="text-xs text-slate-600 mt-1 font-bold">AI-drafted blog posts</div>
            </div>
            <div class="bg-white rounded-xl p-4">
                <div class="text-3xl font-display font-black text-rose-700"><?= number_format($aiStats['campaigns_ai']) ?></div>
                <div class="text-xs text-slate-600 mt-1 font-bold">AI-drafted campaigns</div>
            </div>
            <div class="bg-white rounded-xl p-4">
                <div class="text-3xl font-display font-black text-amber-700"><?= number_format($aiStats['imports_total']) ?></div>
                <div class="text-xs text-slate-600 mt-1 font-bold">Products auto-imported</div>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-600">Provider-agnostic — Claude / GPT / Gemini / Kimi swappable via one env var.</p>
    </div>

    <!-- Traffic sources + Recent activity -->
    <div class="grid lg:grid-cols-[1fr_350px] gap-4">
        <div class="bg-white rounded-2xl shadow-soft p-5">
            <h2 class="font-display font-black text-lg">🌐 Traffic sources · 30 days</h2>
            <?php if (empty($cities)): ?>
                <p class="mt-3 text-sm text-slate-500">No attribution data yet — UTM filter is live; orders will start showing source here.</p>
            <?php else: ?>
                <table class="w-full mt-3 text-sm">
                    <thead class="text-xs uppercase tracking-wider text-slate-500 border-b">
                        <tr><th class="text-left py-2">Source</th><th class="text-right py-2">Orders</th><th class="text-right py-2">GMV</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cities as $c): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-2 capitalize"><?= esc($c['src'] ?: 'direct') ?></td>
                                <td class="py-2 text-right tabular-nums"><?= (int) $c['orders'] ?></td>
                                <td class="py-2 text-right tabular-nums font-bold"><?= $money((int) $c['gmv']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-soft p-5">
            <h2 class="font-display font-black text-lg">⚡ Live activity</h2>
            <ol class="mt-3 space-y-2 text-sm">
                <?php if (empty($activity)): ?><li class="text-slate-500">Quiet right now.</li><?php endif; ?>
                <?php foreach ($activity as $a):
                    $icon = ['order'=>'🛒','intent'=>'🎯','review'=>'⭐'][$a['kind']] ?? '•';
                ?>
                    <li class="flex items-start gap-2 border-b border-slate-100 pb-2 last:border-0">
                        <span class="text-base shrink-0"><?= $icon ?></span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs">
                                <strong class="capitalize"><?= esc($a['kind']) ?></strong> · <span class="text-slate-700"><?= esc($a['label']) ?></span>
                            </div>
                            <div class="text-[10px] text-slate-500"><?= esc($a['who'] ?: '—') ?> · <?= date('j M, g:i A', strtotime($a['created_at'])) ?></div>
                            <?php if ($a['amt'] > 0): ?><div class="text-[11px] font-bold text-emerald-600"><?= $money((int) $a['amt']) ?></div><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>

    <!-- Founder narrative -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-8">
        <span class="eyebrow text-amber-400">💡 The thesis</span>
        <h2 class="h-display text-2xl sm:text-3xl mt-2">Khoobie is the first Indian marketplace built for screen-free childhood</h2>
        <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-slate-200">
            <div>
                <div class="font-display font-black text-amber-400">🎁 Products</div>
                <p class="mt-1">Own + sourced + drop-shipped + digital — all 4 SKU models in one cart, one checkout, one fulfilment grid.</p>
            </div>
            <div>
                <div class="font-display font-black text-rose-400">🎓 Classes</div>
                <p class="mt-1">Online live + recorded + in-person · indexed by city → locality → area for India's "near me" search intent.</p>
            </div>
            <div>
                <div class="font-display font-black text-violet-400">🤖 AI ops</div>
                <p class="mt-1">Content, imports, recommendations, concierge — staffed by LLMs. Operations stay lean as catalog scales.</p>
            </div>
            <div>
                <div class="font-display font-black text-emerald-400">🔄 Flywheel</div>
                <p class="mt-1">Kit → class → bundle → membership → referral → repeat. Each surface compounds the others.</p>
            </div>
            <div>
                <div class="font-display font-black text-sky-400">🤝 Marketplace</div>
                <p class="mt-1">Self-serve onboarding for brands + instructors. Drop-ship, warehouse-share, weekly payouts — all built.</p>
            </div>
            <div>
                <div class="font-display font-black text-amber-400">📍 Hyperlocal</div>
                <p class="mt-1">Sticky city selector + auto-filtered listings + nightly demand-gap cron tells ops where to recruit next.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
