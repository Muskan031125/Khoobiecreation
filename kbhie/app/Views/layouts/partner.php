<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($page['title'] ?? 'Partner Portal') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
</head>
<body class="bg-slate-100 antialiased font-sans">

<div class="min-h-screen flex flex-col">
    <header class="bg-slate-900 text-white">
        <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-500 font-black">K</span>
                <div>
                    <div class="font-bold leading-tight">Partner Portal</div>
                    <div class="text-xs text-slate-400"><?= esc($partner['company_name'] ?? '—') ?></div>
                </div>
            </div>
            <nav class="hidden md:flex items-center gap-5 text-sm">
                <a href="<?= base_url('partner') ?>" class="hover:text-brand-300">Dashboard</a>
                <a href="<?= base_url('partner/orders') ?>" class="hover:text-brand-300">Orders</a>
                <a href="<?= base_url('partner/inventory') ?>" class="hover:text-brand-300">Inventory</a>
                <a href="<?= base_url('partner/payouts') ?>" class="hover:text-brand-300">Payouts</a>
                <a href="<?= base_url('partner/logout') ?>" class="text-rose-300 hover:text-rose-200">Log out</a>
            </nav>
        </div>
    </header>
    <main class="flex-1 mx-auto max-w-7xl w-full px-4 py-6 lg:py-10">
        <?php if (session('success')): ?>
            <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"><?= esc(session('error')) ?></div>
        <?php endif; ?>
        <?= $this->renderSection('content') ?>
    </main>
</div>

<script src="<?= base_url('assets/app.js') ?>" defer></script>
</body>
</html>
