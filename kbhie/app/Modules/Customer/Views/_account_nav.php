<nav class="bg-white rounded-2xl shadow-sm p-3 h-fit sticky top-20">
    <?php
    $items = [
        ['account',                'Dashboard',      '🏠'],
        ['account/orders',         'My Orders',      '📦'],
        ['account/buy-again',      'Buy Again',      '🔁'],
        ['account/downloads',      'My Downloads',   '⚡'],
        ['account/wallet',         'Khoobie Wallet', '⭐'],
        ['shortlist',              'Shortlist',      '❤'],
        ['account/addresses',      'Addresses',      '📍'],
        ['account/referrals',      'Refer & Earn',   '🎁'],
        ['account/subscriptions',  'Subscriptions',  '🔄'],
        ['account/profile',        'Profile',        '👤'],
    ];
    $current = trim(parse_url(current_url(), PHP_URL_PATH), '/');
    foreach ($items as [$href, $label, $icon]):
        $active = str_ends_with($current, trim($href, '/'));
    ?>
    <a href="<?= base_url($href) ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?= $active ? 'bg-brand-50 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-50' ?>">
        <span class="text-base"><?= $icon ?></span>
        <span><?= esc($label) ?></span>
    </a>
    <?php endforeach; ?>
    <a href="<?= base_url('logout') ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
        <span class="text-base">↩</span>
        <span>Log out</span>
    </a>
</nav>
