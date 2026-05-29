<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $ship = json_decode($row['shipping_address'] ?? '{}', true) ?: []; ?>

<div class="max-w-4xl space-y-4">
    <a href="<?= base_url('admin/returns') ?>" class="text-sm text-slate-500 hover:underline">← All returns</a>

    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-start justify-between flex-wrap gap-3">
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Return Request</div>
                <h1 class="text-2xl font-display font-black mt-1"><?= esc($row['return_number']) ?></h1>
                <div class="text-sm text-slate-500 mt-0.5">Filed <?= date('j M Y, g:i A', strtotime($row['created_at'])) ?></div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold capitalize bg-amber-100 text-amber-700"><?= esc($row['status']) ?></span>
        </div>

        <div class="mt-4 grid sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Order</div>
                <a href="<?= base_url('admin/orders/' . $row['order_id']) ?>" class="text-brand-600 font-bold hover:underline">#<?= esc($row['order_number']) ?></a>
                <div class="text-xs text-slate-500">Order total: <?= kb_money((int) $row['grand_total']) ?></div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Customer</div>
                <div class="font-bold"><?= esc($row['customer_name']) ?></div>
                <div class="text-xs text-slate-500"><?= esc($row['phone']) ?> · <?= esc($row['email']) ?></div>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100">
            <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Reason</div>
            <div class="font-semibold mt-1"><?= esc($row['reason']) ?></div>
            <?php if (! empty($row['description'])): ?>
                <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= esc($row['description']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (! empty($items)): ?>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Items being returned</div>
                <ul class="mt-2 space-y-1 text-sm">
                    <?php foreach ($items as $it): $snap = json_decode($it['product_snapshot'] ?? '{}', true) ?: []; ?>
                        <li class="flex justify-between"><span><?= esc($snap['name'] ?? '') ?> × <?= (int) ($it['qty'] ?? 1) ?></span><span class="font-semibold"><?= kb_money((int) ($it['line_total'] ?? 0)) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (! empty($ship)): ?>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Pickup from</div>
                <div class="text-sm text-slate-700 mt-1">
                    <?= esc($ship['line1'] ?? '') ?><?= ! empty($ship['line2']) ? ', ' . esc($ship['line2']) : '' ?>,
                    <?= esc($ship['city'] ?? '') ?>, <?= esc($ship['state'] ?? '') ?> — <?= esc($ship['pincode'] ?? '') ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <?php if (in_array($row['status'], ['requested'], true)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-display font-black text-lg">Actions</h2>
            <div class="mt-4 grid sm:grid-cols-2 gap-3">
                <form method="post" action="<?= base_url('admin/returns/' . $row['id'] . '/approve') ?>" class="bg-emerald-50 rounded-xl p-4 space-y-2">
                    <?= csrf_field() ?>
                    <h3 class="font-bold text-sm text-emerald-700">✓ Approve return</h3>
                    <input name="refund_inr" type="number" min="0" step="1" required placeholder="Refund amount ₹" class="w-full px-3 py-2 rounded-lg border-2 border-emerald-200">
                    <textarea name="note" rows="2" placeholder="Internal note (optional)" class="w-full px-3 py-2 rounded-lg border-2 border-emerald-200 text-sm"></textarea>
                    <button class="w-full px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold">Approve &amp; notify</button>
                </form>
                <form method="post" action="<?= base_url('admin/returns/' . $row['id'] . '/reject') ?>" class="bg-rose-50 rounded-xl p-4 space-y-2">
                    <?= csrf_field() ?>
                    <h3 class="font-bold text-sm text-rose-700">✕ Reject return</h3>
                    <textarea name="reason" rows="3" required placeholder="Reason (customer will see this)" class="w-full px-3 py-2 rounded-lg border-2 border-rose-200 text-sm"></textarea>
                    <button class="w-full px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold">Reject &amp; notify</button>
                </form>
            </div>
        </div>
    <?php elseif (in_array($row['status'], ['approved','received'], true)): ?>
        <form method="post" action="<?= base_url('admin/returns/' . $row['id'] . '/refunded') ?>" class="bg-white rounded-2xl shadow-sm p-6">
            <?= csrf_field() ?>
            <h2 class="font-display font-black text-lg">Mark as refunded</h2>
            <p class="text-sm text-slate-600 mt-1">After you've processed the refund through Razorpay/PhonePe console, click below.</p>
            <button class="mt-3 btn-primary">💸 Mark refund complete</button>
        </form>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
