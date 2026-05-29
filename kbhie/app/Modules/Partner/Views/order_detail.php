<?= $this->extend('layouts/partner') ?>
<?= $this->section('content') ?>

<?php $ship = json_decode($order['shipping_address'] ?? '{}', true) ?: []; ?>
<div class="grid lg:grid-cols-[1fr_320px] gap-6">
    <div class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <a href="<?= base_url('partner/orders') ?>" class="text-xs text-slate-500 hover:underline">&larr; Back</a>
            <h1 class="mt-1 text-2xl font-black">Order #<?= esc($order['order_number']) ?></h1>
            <div class="text-sm text-slate-500"><?= kb_date($order['created_at'], true) ?></div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm divide-y divide-slate-100">
            <?php foreach ($items as $it): $snap = json_decode($it['product_snapshot'] ?? '{}', true) ?: []; ?>
                <div class="p-4 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-slate-100"></div>
                    <div class="flex-1">
                        <div class="font-semibold"><?= esc($snap['name'] ?? 'Product') ?></div>
                        <div class="text-xs text-slate-500">Qty <?= (int) $it['qty'] ?> · <?= kb_money((int)($it['line_total'])) ?></div>
                    </div>
                    <span class="text-xs font-semibold"><?= esc($it['fulfillment_status']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <aside class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h2 class="font-bold">Ship to</h2>
            <div class="mt-2 text-sm space-y-0.5">
                <div class="font-semibold"><?= esc($order['name']) ?></div>
                <div><?= esc($order['phone']) ?></div>
                <div class="text-slate-700"><?= esc($ship['line1'] ?? '') ?></div>
                <div class="text-slate-700"><?= esc($ship['city'] ?? '') ?>, <?= esc($ship['state'] ?? '') ?> <?= esc($ship['pincode'] ?? '') ?></div>
            </div>
        </div>

        <?php if (! $shipment || $shipment['status'] !== 'shipped'): ?>
            <form method="post" action="<?= base_url('partner/orders/' . $order['id'] . '/ship') ?>" class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
                <?= csrf_field() ?>
                <h2 class="font-bold">Mark as shipped</h2>
                <input name="courier" placeholder="Courier (e.g. Delhivery)" class="w-full px-3 py-2 rounded border border-slate-200 text-sm">
                <input name="awb" placeholder="AWB / tracking number" class="w-full px-3 py-2 rounded border border-slate-200 text-sm">
                <button class="w-full btn-primary">Ship now</button>
            </form>
        <?php else: ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5">
                <h2 class="font-bold text-emerald-800">Shipped</h2>
                <p class="mt-1 text-sm text-emerald-700"><?= esc($shipment['courier']) ?> · AWB <?= esc($shipment['awb']) ?></p>
                <form method="post" action="<?= base_url('partner/orders/' . $order['id'] . '/awb') ?>" class="mt-3 flex gap-2">
                    <?= csrf_field() ?>
                    <input name="awb" value="<?= esc($shipment['awb']) ?>" class="flex-1 px-3 py-2 rounded border border-emerald-200 text-sm">
                    <button class="px-3 py-2 rounded bg-emerald-600 text-white text-sm font-semibold">Update</button>
                </form>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?= $this->endSection() ?>
