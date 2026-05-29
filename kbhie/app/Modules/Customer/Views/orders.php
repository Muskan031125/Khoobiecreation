<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-8 lg:py-12 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4">
        <div class="grid lg:grid-cols-[260px_1fr] gap-6">
            <?= $this->include('App\Modules\Customer\Views\_account_nav') ?>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h1 class="text-xl font-black">My Orders</h1>
                <?php if (empty($orders)): ?>
                    <p class="mt-4 text-sm text-slate-500">No orders yet. <a href="<?= base_url('shop') ?>" class="text-brand-600 font-semibold">Start shopping &rarr;</a></p>
                <?php else: ?>
                    <ul class="mt-4 divide-y divide-slate-100">
                    <?php foreach ($orders as $o): ?>
                        <li class="py-4">
                            <a href="<?= base_url('account/orders/' . $o['id']) ?>" class="block hover:bg-slate-50 -mx-2 px-2 py-2 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-bold">#<?= esc($o['order_number']) ?></div>
                                        <div class="text-xs text-slate-500 mt-1"><?= esc($o['status']) ?> · <?= kb_date($o['created_at']) ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-black"><?= kb_money((int)($o['grand_total'])) ?></div>
                                        <div class="text-xs text-slate-500"><?= esc($o['payment_mode'] ?: '—') ?></div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
