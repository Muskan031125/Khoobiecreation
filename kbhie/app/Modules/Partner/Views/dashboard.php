<?= $this->extend('layouts/partner') ?>
<?= $this->section('content') ?>

<h1 class="text-2xl font-black">Welcome, <?= esc($user['name'] ?? '') ?> 👋</h1>
<p class="text-slate-500 text-sm mt-1">Here's your fulfillment snapshot.</p>

<div class="mt-6 grid sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-100">
        <div class="text-xs uppercase tracking-wide text-slate-500">Orders to ship</div>
        <div class="mt-1 text-3xl font-black"><?= number_format($stats['orders_to_ship'] ?? 0) ?></div>
        <a href="<?= base_url('partner/orders') ?>" class="mt-2 inline-block text-xs text-brand-600 font-semibold">View &rarr;</a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-100">
        <div class="text-xs uppercase tracking-wide text-slate-500">Already shipped</div>
        <div class="mt-1 text-3xl font-black"><?= number_format($stats['orders_shipped'] ?? 0) ?></div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-100">
        <div class="text-xs uppercase tracking-wide text-slate-500">Active SKUs</div>
        <div class="mt-1 text-3xl font-black"><?= number_format($stats['products_count'] ?? 0) ?></div>
        <a href="<?= base_url('partner/products') ?>" class="mt-2 inline-block text-xs text-brand-600 font-semibold">Manage &rarr;</a>
    </div>
</div>

<div class="mt-8 bg-white rounded-2xl shadow-sm p-6">
    <h2 class="font-bold">Getting started</h2>
    <ul class="mt-2 text-sm text-slate-600 space-y-1">
        <li>Orders are routed to you automatically when a customer buys one of your SKUs.</li>
        <li>Mark items as packed → shipped → entered AWB; tracking updates flow to the customer automatically.</li>
        <li>Payouts are settled per the period set in your contract.</li>
    </ul>
</div>

<?= $this->endSection() ?>
