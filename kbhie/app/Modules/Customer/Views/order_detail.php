<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-8 lg:py-12 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4">
        <div class="grid lg:grid-cols-[260px_1fr] gap-6">
            <?= $this->include('App\Modules\Customer\Views\_account_nav') ?>
            <div class="space-y-4">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <a href="<?= base_url('account/orders') ?>" class="text-xs text-slate-500 hover:underline">&larr; Back to orders</a>
                    <h1 class="mt-2 text-xl font-black">Order #<?= esc($order['order_number']) ?></h1>
                    <div class="mt-1 text-sm text-slate-500"><?= esc($order['status']) ?> · placed <?= kb_date($order['created_at']) ?></div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm divide-y divide-slate-100">
                    <?php foreach ($items as $it): $snap = json_decode($it['product_snapshot'] ?? '{}', true) ?: []; ?>
                        <div class="p-4 flex items-center gap-3">
                            <div class="w-14 h-14 rounded-lg bg-slate-100"></div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm"><?= esc($snap['name'] ?? 'Product') ?></div>
                                <div class="text-xs text-slate-500">Qty <?= (int) $it['qty'] ?> · <?= kb_money((int)($it['line_total'])) ?></div>
                            </div>
                            <div class="text-xs font-semibold text-slate-700"><?= esc($it['fulfillment_status']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-6 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span><?= kb_money((int)($order['subtotal'])) ?></span></div>
                    <div class="flex justify-between"><span>Discount</span><span>&minus; <?= kb_money((int)($order['discount_total'])) ?></span></div>
                    <div class="flex justify-between"><span>Shipping</span><span><?= kb_money((int)($order['shipping_total'])) ?></span></div>
                    <div class="flex justify-between"><span>Tax</span><span><?= kb_money((int)($order['tax_total'])) ?></span></div>
                    <div class="mt-2 pt-2 border-t border-slate-200 flex justify-between font-black text-base"><span>Total</span><span><?= kb_money((int)($order['grand_total'])) ?></span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
