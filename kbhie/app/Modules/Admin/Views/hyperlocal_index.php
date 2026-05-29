<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="space-y-6 max-w-7xl">

    <div>
        <h1 class="text-2xl font-black">📍 Hyperlocal Demand Intelligence</h1>
        <p class="text-sm text-slate-500 mt-1">Where to recruit instructors / studios next.</p>
        <?php if ($latest): ?>
            <p class="text-xs text-slate-400 mt-1">Last snapshot: <?= date('j M Y, g:i A', strtotime($latest->created_at)) ?> · <?= count($gaps) ?> gaps flagged</p>
        <?php else: ?>
            <p class="text-xs text-amber-600 mt-1">No snapshots yet. Run <code class="font-mono bg-slate-100 px-1 rounded">php spark hyperlocal:demand</code> to generate.</p>
        <?php endif; ?>
    </div>

    <!-- City rollup -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-display font-black text-lg">By city · last 30 days</h2>
        <table class="w-full mt-3 text-sm">
            <thead class="text-xs uppercase tracking-wider text-slate-500 border-b">
                <tr><th class="text-left py-2">City</th><th class="text-right py-2">Supply</th><th class="text-right py-2">Intents</th><th class="text-right py-2">Demand/Supply</th></tr>
            </thead>
            <tbody>
                <?php foreach ($cityRollup as $c):
                    $ratio = $c['supply'] > 0 ? round($c['intents_30d'] / $c['supply'], 1) : ($c['intents_30d'] > 0 ? '∞' : 0);
                    $hot = is_numeric($ratio) && $ratio >= 5;
                ?>
                    <tr class="border-b last:border-0">
                        <td class="py-2 font-semibold"><?= esc($c['city']) ?></td>
                        <td class="py-2 text-right tabular-nums"><?= (int) $c['supply'] ?></td>
                        <td class="py-2 text-right tabular-nums"><?= (int) $c['intents_30d'] ?></td>
                        <td class="py-2 text-right tabular-nums <?= $hot ? 'font-black text-rose-600' : 'text-slate-700' ?>">
                            <?= is_numeric($ratio) ? number_format($ratio, 1) . 'x' : $ratio ?>
                            <?= $hot ? ' 🔥' : '' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Latest snapshot gaps -->
    <?php if (! empty($gaps)): ?>
        <div class="bg-rose-50 border-2 border-dashed border-rose-300 rounded-2xl p-5">
            <h2 class="font-display font-black text-lg text-rose-700">⚠ Latest gaps — recruit here</h2>
            <p class="text-sm text-slate-600 mt-1">From snapshot on <?= date('j M', strtotime($latest->snapshot_date)) ?>. Demand ≥ <?= $latest->threshold ?> users, supply &lt; 3.</p>
            <table class="w-full mt-3 text-sm">
                <thead class="text-xs uppercase tracking-wider text-slate-500 border-b border-rose-200">
                    <tr><th class="text-left py-2">City</th><th class="text-left py-2">Locality</th><th class="text-right py-2">Interested</th><th class="text-right py-2">Supply</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($gaps as $g): ?>
                        <tr class="border-b border-rose-200 last:border-0">
                            <td class="py-2 font-semibold"><?= esc($g['city']) ?></td>
                            <td class="py-2"><?= esc($g['locality']) ?></td>
                            <td class="py-2 text-right tabular-nums font-bold text-rose-700"><?= (int) $g['interested_users'] ?></td>
                            <td class="py-2 text-right tabular-nums"><?= (int) $g['current_supply'] ?></td>
                            <td class="py-2 text-right">
                                <a href="<?= base_url('admin/leads?q=' . urlencode($g['city'])) ?>" class="text-xs font-bold text-brand-600 hover:underline">Filter leads →</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Live granular table -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h2 class="font-display font-black text-lg">Live · by (city, locality)</h2>
        <table class="w-full mt-3 text-sm">
            <thead class="text-xs uppercase tracking-wider text-slate-500 border-b">
                <tr><th class="text-left py-2">City</th><th class="text-left py-2">Locality</th><th class="text-right py-2">Supply</th><th class="text-right py-2">Intents 30d</th></tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($live, 0, 50) as $l): ?>
                    <tr class="border-b last:border-0">
                        <td class="py-2"><?= esc($l['city']) ?></td>
                        <td class="py-2 text-slate-600"><?= esc($l['locality']) ?: '—' ?></td>
                        <td class="py-2 text-right tabular-nums"><?= (int) $l['supply_n'] ?></td>
                        <td class="py-2 text-right tabular-nums font-semibold"><?= (int) $l['intents_30d'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
