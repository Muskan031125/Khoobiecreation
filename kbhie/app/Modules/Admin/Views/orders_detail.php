<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $ship = json_decode($order['shipping_address'] ?? '{}', true) ?: []; ?>

<div class="grid lg:grid-cols-[1fr_320px] gap-6">
    <div class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs text-slate-500">Order</div>
                    <h1 class="text-2xl font-black mt-1">#<?= esc($order['order_number']) ?></h1>
                    <div class="mt-1 text-sm text-slate-500">Placed <?= kb_date($order['created_at'], true) ?></div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= in_array($order['status'], ['pending_payment','pending_confirmation']) ? 'bg-amber-100 text-amber-700' : ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700') ?>">
                    <?= esc(str_replace('_',' ',$order['status'])) ?>
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm">
            <h2 class="px-5 py-4 font-bold border-b border-slate-100">Items</h2>
            <div class="divide-y divide-slate-100">
                <?php foreach ($items as $it): $snap = json_decode($it['product_snapshot'] ?? '{}', true) ?: []; ?>
                    <div class="p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-slate-100"></div>
                        <div class="flex-1">
                            <div class="font-semibold"><?= esc($snap['name'] ?? 'Product') ?></div>
                            <div class="text-xs text-slate-500">SKU <?= esc($snap['sku'] ?? '') ?> · Qty <?= (int) $it['qty'] ?> · <?= esc($it['fulfillment_status']) ?></div>
                        </div>
                        <div class="font-bold"><?= kb_money((int)($it['line_total'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="border-t border-slate-100 p-5 text-sm space-y-1">
                <div class="flex justify-between"><span>Subtotal</span><span><?= kb_money((int)($order['subtotal'])) ?></span></div>
                <div class="flex justify-between"><span>Discount</span><span>− <?= kb_money((int)($order['discount_total'])) ?></span></div>
                <div class="flex justify-between"><span>Shipping</span><span><?= kb_money((int)($order['shipping_total'])) ?></span></div>
                <?php if ($order['cod_fee'] > 0): ?><div class="flex justify-between"><span>COD fee</span><span><?= kb_money((int)($order['cod_fee'])) ?></span></div><?php endif; ?>
                <div class="flex justify-between"><span>Tax</span><span><?= kb_money((int)($order['tax_total'])) ?></span></div>
                <div class="border-t border-slate-200 mt-2 pt-2 flex justify-between font-black text-lg"><span>Total</span><span><?= kb_money((int)($order['grand_total'])) ?></span></div>
                <div class="text-xs text-slate-500 mt-2">Paid <?= kb_money((int)($order['amount_paid'])) ?> · Due <?= kb_money((int)($order['amount_due'])) ?></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h2 class="font-bold">Status history</h2>
            <ol class="mt-3 space-y-2 text-sm">
                <?php foreach ($history as $h): ?>
                    <li class="flex gap-3"><div class="w-2 h-2 mt-2 rounded-full bg-brand-500"></div><div>
                        <div><span class="font-semibold"><?= esc($h['to_status']) ?></span> <span class="text-xs text-slate-500">via <?= esc($h['channel']) ?></span></div>
                        <div class="text-xs text-slate-500"><?= kb_date($h['created_at'], true, 'short') ?> <?= $h['note'] ? '· ' . esc($h['note']) : '' ?></div>
                    </div></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h2 class="font-bold">Customer</h2>
            <div class="mt-2 text-sm space-y-1">
                <div class="font-semibold"><?= esc($order['name']) ?></div>
                <div><?= esc($order['email']) ?></div>
                <div><a href="tel:<?= esc($order['phone']) ?>" class="text-brand-600 font-semibold">📞 <?= esc($order['phone']) ?></a></div>
                <?php if ($order['phone']): ?><div><a target="_blank" href="https://wa.me/<?= esc(preg_replace('/\D/', '', $order['phone'])) ?>" class="text-emerald-600 font-semibold">💬 WhatsApp</a></div><?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h2 class="font-bold">Shipping address</h2>
            <div class="mt-2 text-sm text-slate-700 space-y-0.5">
                <div><?= esc($ship['line1'] ?? '') ?></div>
                <?php if (! empty($ship['line2'])): ?><div><?= esc($ship['line2']) ?></div><?php endif; ?>
                <div><?= esc($ship['city'] ?? '') ?>, <?= esc($ship['state'] ?? '') ?> <?= esc($ship['pincode'] ?? '') ?></div>
            </div>
        </div>

        <?php if ($order['confirmation_status'] === 'pending'): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                <h2 class="font-bold text-amber-900">Awaiting confirmation</h2>
                <p class="mt-1 text-xs text-amber-800">Call the customer and confirm the order before shipping.</p>
                <form method="post" action="<?= base_url('admin/orders/' . $order['id'] . '/confirm') ?>" class="mt-3 space-y-2">
                    <?= csrf_field() ?>
                    <select name="channel" class="w-full px-3 py-2 rounded border border-amber-200 text-sm">
                        <option value="phone">Phone call</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                    </select>
                    <input name="note" placeholder="Note (optional)" class="w-full px-3 py-2 rounded border border-amber-200 text-sm">
                    <button class="w-full px-3 py-2 rounded bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm">Mark as confirmed</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (! in_array($order['status'], ['cancelled','delivered','refunded'])): ?>
            <form method="post" action="<?= base_url('admin/orders/' . $order['id'] . '/cancel') ?>" onsubmit="return confirm('Cancel this order?')">
                <?= csrf_field() ?>
                <input name="reason" placeholder="Cancellation reason" class="w-full px-3 py-2 mb-2 rounded border border-red-200 text-sm">
                <button class="w-full px-3 py-2 rounded bg-red-50 hover:bg-red-100 text-red-700 font-semibold text-sm border border-red-200">Cancel order</button>
            </form>
        <?php endif; ?>

        <a href="<?= base_url('admin/orders') ?>" class="block text-center text-xs text-slate-500 hover:underline">&larr; All orders</a>
    </aside>
</div>

<?= $this->endSection() ?>
