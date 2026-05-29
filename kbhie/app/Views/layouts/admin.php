<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($page['title'] ?? 'Khoobie Admin') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
<style>[x-cloak]{display:none !important}</style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased font-sans">

<?php
// ---------------------------------------------------------------------------
// Sidebar definition. Each section: [label, icon, items[]]. Item: [href, label].
// `current` is the path after /admin/ (eg "products" or "orders/12").
// ---------------------------------------------------------------------------
$currentPath = trim((string) parse_url(current_url(), PHP_URL_PATH), '/');
// strip "kbhie/" or app prefix if present, then leading "admin/"
$currentPath = preg_replace('#^[^/]*/?admin/?#', '', $currentPath);

$sidebar = [
    'overview' => ['label' => 'Overview', 'icon' => '🏠', 'items' => [
        ['admin',           'Dashboard'],
        ['admin/reports',   'Reports'],
    ]],
    'catalog' => ['label' => 'Catalog', 'icon' => '🎨', 'items' => [
        ['admin/products',   'Products'],
        ['admin/categories', 'Categories'],
        ['admin/variants',   'Variants'],
        ['admin/bundles',    'Bundles'],
        ['admin/inventory',  'Inventory'],
        ['admin/warehouses', 'Warehouses'],
    ]],
    'sales' => ['label' => 'Sales', 'icon' => '🛒', 'items' => [
        ['admin/orders',     'Orders'],
        ['admin/shipments',  'Shipments'],
        ['admin/returns',    'Returns'],
        ['admin/invoices',   'GST Invoices'],
    ]],
    'customers' => ['label' => 'Customers', 'icon' => '👥', 'items' => [
        ['admin/customers',   'Customers'],
        ['admin/leads',       'Leads'],
        ['admin/subscribers', 'Subscribers'],
    ]],
    'marketing' => ['label' => 'Marketing', 'icon' => '📣', 'items' => [
        ['admin/coupons',       'Coupons'],
        ['admin/promotions',    'Promotions'],
        ['admin/gift-cards',    'Gift Cards'],
        ['admin/loyalty-rules', 'Loyalty Rules'],
        ['admin/campaigns',     'Notification Log'],
    ]],
    'partners' => ['label' => 'Partnerships', 'icon' => '🤝', 'items' => [
        ['admin/partners',   'Partners'],
        ['admin/payouts',    'Payouts'],
        ['admin/affiliates', 'Affiliates'],
    ]],
    'subs_events' => ['label' => 'Subs & Events', 'icon' => '🎟️', 'items' => [
        ['admin/subscriptions',      'Subscriptions'],
        ['admin/subscription-plans', 'Subscription Plans'],
        ['admin/events',             'Events'],
        ['admin/bookings',           'Bookings'],
    ]],
    'system' => ['label' => 'System', 'icon' => '⚙️', 'items' => [
        ['admin/settings', 'Settings'],
    ]],
];

/** Does the current URL path match this menu href? Allows /admin/orders/5/edit to highlight /admin/orders. */
$isActive = static function (string $href) use ($currentPath): bool {
    $target = trim(preg_replace('#^admin/?#', '', $href), '/');
    $cur    = trim($currentPath, '/');
    if ($target === '' && $cur === '') return true;          // /admin === dashboard
    if ($target === '' && $cur !== '') return false;
    if ($cur === $target) return true;
    return str_starts_with($cur, $target . '/');
};

// Auto-expand the section that contains the active page
$openSection = 'overview';
foreach ($sidebar as $key => $sec) {
    foreach ($sec['items'] as [$href]) {
        if ($isActive($href)) { $openSection = $key; break 2; }
    }
}

// Build breadcrumb crumbs (admin > section > current label)
$crumbs = [['Admin', base_url('admin')]];
foreach ($sidebar as $sec) {
    foreach ($sec['items'] as [$href, $label]) {
        if ($isActive($href)) {
            $crumbs[] = [$sec['label'], null];
            $crumbs[] = [$label, base_url($href)];
            break 2;
        }
    }
}
?>

<div class="min-h-screen flex" x-data="{ sidebarOpen: false, openSection: '<?= esc($openSection, 'js') ?>' }">

    <!-- Mobile backdrop -->
    <div x-show="sidebarOpen" x-transition.opacity x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <!-- Sidebar -->
    <aside
        class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-300 flex flex-col transform transition-transform lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
            <a href="<?= base_url('admin') ?>" class="flex items-center gap-2">
                <img src="<?= base_url('assets/brand/logo.png') ?>" class="h-8 w-auto bg-white p-1 rounded" alt="Khoobie">
                <div>
                    <div class="font-black text-white text-sm leading-tight">Khoobie</div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-500">Admin</div>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white" aria-label="Close menu">✕</button>
        </div>

        <nav class="flex-1 overflow-y-auto py-2 text-sm">
            <?php foreach ($sidebar as $key => $section):
                $isOpen = false;
                foreach ($section['items'] as [$href]) {
                    if ($isActive($href)) { $isOpen = true; break; }
                }
            ?>
                <div>
                    <button
                        @click="openSection = openSection === '<?= esc($key, 'js') ?>' ? null : '<?= esc($key, 'js') ?>'"
                        :class="openSection === '<?= esc($key, 'js') ?>' ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                        class="w-full flex items-center justify-between px-5 py-2.5 text-left font-semibold transition">
                        <span class="flex items-center gap-3">
                            <span class="text-base"><?= $section['icon'] ?></span>
                            <?= esc($section['label']) ?>
                        </span>
                        <svg class="w-3 h-3 transition" :class="openSection === '<?= esc($key, 'js') ?>' ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg>
                    </button>
                    <div x-show="openSection === '<?= esc($key, 'js') ?>'" x-collapse>
                        <div class="bg-slate-950/40 py-1">
                            <?php foreach ($section['items'] as [$href, $label]):
                                $active = $isActive($href); ?>
                                <a href="<?= base_url($href) ?>"
                                   class="block pl-12 pr-5 py-2 text-[13px] transition <?= $active ? 'bg-brand-500/15 text-white font-bold border-l-2 border-brand-500' : 'text-slate-400 hover:text-white' ?>">
                                    <?= esc($label) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="border-t border-slate-800 p-4 text-xs">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-xs">
                    <?= strtoupper(substr(session('user')['name'] ?? '?', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-slate-200 font-semibold truncate"><?= esc(session('user')['name'] ?? '') ?></div>
                    <div class="text-slate-500 truncate"><?= esc(session('user')['email'] ?? '') ?></div>
                </div>
            </div>
            <a href="<?= base_url('admin/logout') ?>" class="mt-3 block text-center px-3 py-1.5 rounded-md bg-slate-800 hover:bg-rose-600 text-slate-300 hover:text-white font-semibold transition">Log out</a>
        </div>
    </aside>

    <!-- Main column -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top bar -->
        <header class="sticky top-0 z-20 bg-white border-b border-slate-200 px-4 lg:px-8 py-3 flex items-center gap-4">
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 -ml-2 rounded-md hover:bg-slate-100" aria-label="Open menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <!-- Global admin search -->
            <form method="get" action="<?= base_url('admin/products') ?>" class="flex-1 max-w-md hidden sm:block">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input name="q" placeholder="Search products… (press / )" class="w-full pl-10 pr-3 py-2 rounded-lg bg-slate-100 hover:bg-white border border-transparent focus:bg-white focus:border-brand-400 focus:outline-none text-sm">
                </div>
            </form>
            <div class="ml-auto flex items-center gap-2">
                <span class="hidden md:inline text-xs text-slate-500"><?= kb_date(date('Y-m-d H:i:s'), true, 'short') ?></span>
                <a href="<?= base_url('/') ?>" target="_blank" class="hidden md:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm font-semibold">
                    View storefront <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="p-2 rounded-full hover:bg-slate-100" aria-label="New">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-slate-100 py-1 text-sm">
                        <a href="<?= base_url('admin/products/new') ?>" class="block px-3 py-2 hover:bg-slate-50">+ New product</a>
                        <a href="<?= base_url('admin/coupons/new') ?>" class="block px-3 py-2 hover:bg-slate-50">+ New coupon</a>
                        <a href="<?= base_url('admin/promotions/new') ?>" class="block px-3 py-2 hover:bg-slate-50">+ New promotion</a>
                        <a href="<?= base_url('admin/categories/new') ?>" class="block px-3 py-2 hover:bg-slate-50">+ New category</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Breadcrumb -->
        <?php if (count($crumbs) > 1): ?>
        <nav class="bg-white border-b border-slate-100 px-4 lg:px-8 py-2 text-xs text-slate-500 flex items-center gap-1 overflow-x-auto whitespace-nowrap">
            <?php foreach ($crumbs as $i => [$lbl, $url]): ?>
                <?php if ($i > 0): ?><span class="text-slate-300">/</span><?php endif; ?>
                <?php if ($url): ?>
                    <a href="<?= esc($url) ?>" class="hover:text-brand-600"><?= esc($lbl) ?></a>
                <?php else: ?>
                    <span class="font-semibold text-slate-700"><?= esc($lbl) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <main class="flex-1 p-4 lg:p-8 overflow-y-auto">
            <div class="flex items-center justify-between mb-5 gap-3 flex-wrap">
                <h1 class="text-xl font-black text-slate-900"><?= esc($page['title'] ?? 'Admin') ?></h1>
                <?= $this->renderSection('actions') ?>
            </div>

            <?php if (session('success')): ?>
                <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-start gap-2">
                    <span>✓</span><span><?= esc(session('success')) ?></span>
                </div>
            <?php endif; ?>
            <?php if (session('error')): ?>
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-start gap-2">
                    <span>⚠</span><span><?= esc(session('error')) ?></span>
                </div>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>

<script src="<?= base_url('assets/app.js') ?>" defer></script>
<script>
    // Keyboard shortcut: press "/" anywhere to focus admin search
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && !e.target.matches('input,textarea')) {
            const s = document.querySelector('header input[name="q"]');
            if (s) { e.preventDefault(); s.focus(); }
        }
    });
    // Confirm before any link or form with data-confirm
    document.body.addEventListener('click', (e) => {
        const el = e.target.closest('[data-confirm]');
        if (el && ! confirm(el.dataset.confirm)) e.preventDefault();
    });
    document.body.addEventListener('submit', (e) => {
        const el = e.target.closest('[data-confirm]');
        if (el && ! confirm(el.dataset.confirm)) e.preventDefault();
    });
</script>
</body>
</html>
