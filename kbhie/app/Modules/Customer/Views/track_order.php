<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php
// Define the order timeline based on order_status_history + status
$stages = [
    ['key' => 'pending_payment',      'label' => 'Order placed',  'icon' => '📝'],
    ['key' => 'pending_confirmation', 'label' => 'Confirmation',  'icon' => '☎️'],
    ['key' => 'processing',           'label' => 'Processing',    'icon' => '📦'],
    ['key' => 'shipped',              'label' => 'Shipped',       'icon' => '🚚'],
    ['key' => 'delivered',            'label' => 'Delivered',     'icon' => '🎉'],
];
// Determine which stage we're at
$visitedStatuses = array_unique(array_column($history, 'to_status'));
$visitedStatuses[] = $order['status'];

function stageReached($key, $visited) {
    $order = ['pending_payment'=>1,'pending_confirmation'=>2,'processing'=>3,'partially_shipped'=>3,'shipped'=>4,'delivered'=>5,'paid'=>4];
    $stageRank = ['pending_payment'=>1,'pending_confirmation'=>2,'processing'=>3,'shipped'=>4,'delivered'=>5][$key] ?? 0;
    $maxRank = 0;
    foreach ($visited as $v) { $r = $order[$v] ?? 0; if ($r > $maxRank) $maxRank = $r; }
    return $maxRank >= $stageRank;
}
$shipment = $shipments[0] ?? null;
$ship = json_decode($order['shipping_address'] ?? '{}', true) ?: [];
?>

<section class="py-6 sm:py-10 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-4xl px-3 sm:px-4 lg:px-6">

        <a href="<?= base_url('account/orders') ?>" class="text-sm text-slate-500 hover:underline">← All orders</a>

        <div class="mt-3 bg-white rounded-2xl shadow-soft p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <span class="eyebrow text-brand-600">Order tracking</span>
                    <h1 class="h-display text-2xl sm:text-3xl mt-1">#<?= esc($order['order_number']) ?></h1>
                    <div class="text-sm text-slate-500 mt-0.5"><?= kb_date($order['created_at'], true) ?></div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 capitalize"><?= esc(str_replace('_',' ',$order['status'])) ?></span>
            </div>

            <!-- Status timeline -->
            <div class="mt-6">
                <div class="flex items-center justify-between gap-1 relative">
                    <div class="absolute top-5 left-0 right-0 h-0.5 bg-slate-200 -z-10"></div>
                    <?php foreach ($stages as $i => $st):
                        $reached = stageReached($st['key'], $visitedStatuses);
                    ?>
                        <div class="flex flex-col items-center text-center flex-1">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-base shadow-sm <?= $reached ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400' ?>"><?= $st['icon'] ?></div>
                            <div class="mt-2 text-[10px] sm:text-xs font-bold <?= $reached ? 'text-emerald-700' : 'text-slate-400' ?>"><?= esc($st['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Shipment info -->
            <?php if ($shipment): ?>
                <div class="mt-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                    <div class="text-xs font-bold uppercase tracking-wider text-emerald-700">📦 Shipment</div>
                    <div class="mt-1 text-sm">
                        <strong><?= esc($shipment['courier'] ?? 'Courier') ?></strong>
                        <?php if (! empty($shipment['awb'])): ?> · AWB <code class="font-mono"><?= esc($shipment['awb']) ?></code><?php endif; ?>
                    </div>
                    <?php if (! empty($shipment['shipped_at'])): ?><div class="text-xs text-emerald-600 mt-0.5">Shipped on <?= kb_date($shipment['shipped_at'], true) ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Items + ship to -->
        <div class="mt-4 grid lg:grid-cols-[1fr_300px] gap-4">
            <div class="bg-white rounded-2xl shadow-soft p-5">
                <h2 class="font-display font-black text-lg">Items</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    <?php foreach ($items as $it): $snap = json_decode($it['product_snapshot'] ?? '{}', true) ?: []; ?>
                        <div class="py-3 flex items-center gap-3">
                            <div class="flex-1">
                                <div class="font-semibold"><?= esc($snap['name'] ?? '') ?></div>
                                <div class="text-xs text-slate-500">Qty <?= (int) $it['qty'] ?> · <?= esc($it['fulfillment_status'] ?? '') ?></div>
                            </div>
                            <div class="font-bold"><?= kb_money((int) $it['line_total']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="space-y-4">
                <div class="bg-white rounded-2xl shadow-soft p-5">
                    <h3 class="font-bold text-sm">🏠 Ship to</h3>
                    <div class="mt-2 text-sm text-slate-700">
                        <div class="font-semibold"><?= esc($order['name']) ?></div>
                        <div><?= esc($order['phone']) ?></div>
                        <div><?= esc($ship['line1'] ?? '') ?></div>
                        <div><?= esc(($ship['city'] ?? '') . ', ' . ($ship['state'] ?? '') . ' ' . ($ship['pincode'] ?? '')) ?></div>
                    </div>
                </div>

                <?php if (! empty($payments)): ?>
                    <div class="bg-white rounded-2xl shadow-soft p-5">
                        <h3 class="font-bold text-sm">💳 Payments</h3>
                        <ul class="mt-2 text-xs space-y-1.5">
                            <?php foreach ($payments as $p): ?>
                                <li class="flex justify-between">
                                    <span class="capitalize"><?= esc($p['gateway']) ?> <?= $p['is_advance'] ? '(advance)' : '' ?></span>
                                    <span class="font-bold"><?= kb_money((int) $p['amount']) ?></span>
                                </li>
                                <li class="text-[10px] text-slate-400 capitalize"><?= esc($p['status']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status history timeline -->
        <?php if (! empty($history)): ?>
            <div class="mt-4 bg-white rounded-2xl shadow-soft p-5">
                <h2 class="font-display font-black text-lg">📋 Timeline</h2>
                <ol class="mt-3 space-y-3">
                    <?php foreach (array_reverse($history) as $h): ?>
                        <li class="flex gap-3 text-sm">
                            <div class="w-2 h-2 rounded-full bg-brand-500 mt-2 shrink-0"></div>
                            <div class="flex-1">
                                <div class="font-semibold capitalize"><?= esc(str_replace('_',' ', $h['to_status'])) ?></div>
                                <?php if (! empty($h['note'])): ?><div class="text-xs text-slate-600 mt-0.5"><?= esc($h['note']) ?></div><?php endif; ?>
                                <div class="text-[10px] text-slate-400 mt-0.5"><?= date('j M Y, g:i A', strtotime($h['created_at'] ?? 'now')) ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
