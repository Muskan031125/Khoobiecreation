<!-- Announcement bar -->
<div class="bg-slate-900 text-white text-xs sm:text-sm py-2 text-center px-4">
    <span class="hidden sm:inline">🎁 FREE shipping on orders ₹999+ • COD available across India • </span>
    <span>Use code <b>WELCOME10</b> for 10% off your first order</span>
</div>

<header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-100" x-data="{ open: false, search: false }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4 h-16 lg:h-20">
            <!-- Logo -->
            <a href="<?= base_url('/') ?>" class="flex items-center gap-2 shrink-0">
                <img src="<?= base_url('assets/brand/logo.png') ?>" alt="<?= esc($brand['name']) ?>" class="h-10 lg:h-12 w-auto">
            </a>

            <!-- Desktop nav -->
            <nav class="hidden lg:flex items-center gap-6 text-sm font-semibold text-slate-800 flex-1 justify-center">
                <a href="<?= base_url('/') ?>" class="hover:text-brand-600 transition">Home</a>
                <a href="<?= base_url('shop/arts') ?>" class="hover:text-brand-600 transition">Learning Kits</a>
                <a href="<?= base_url('shop/nature') ?>" class="hover:text-brand-600 transition">Nature Kits</a>
                <a href="<?= base_url('shop/accessories') ?>" class="hover:text-brand-600 transition">Accessories</a>
                <a href="<?= base_url('shop/classes') ?>" class="hover:text-brand-600 transition inline-flex items-center gap-1">
                    Classes
                    <span class="text-[9px] font-black bg-brand-500 text-white px-1.5 py-0.5 rounded uppercase tracking-wider">New</span>
                </a>
                <a href="<?= base_url('shop/return-gifts') ?>" class="hover:text-brand-600 transition">Return Gifts</a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-1 sm:gap-2">
                <button @click="search = !search" class="p-2 rounded-full hover:bg-slate-100" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
                <a href="<?= base_url('account') ?>" class="hidden sm:inline-flex p-2 rounded-full hover:bg-slate-100" aria-label="Account">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 14a4 4 0 1 0-8 0M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"/></svg>
                </a>
                <a href="<?= base_url('compare') ?>" class="relative hidden sm:inline-flex p-2 rounded-full hover:bg-slate-100" aria-label="Compare">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18M9 7v14M15 12v9M21 5v16"/></svg>
                    <span data-compare-count
                          class="<?= empty($compareIds) ? 'hidden ' : '' ?>absolute -top-1 -right-1 bg-sky-600 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center"><?= count($compareIds ?? []) ?></span>
                </a>
                <a href="<?= base_url('shortlist') ?>" class="relative hidden sm:inline-flex p-2 rounded-full hover:bg-slate-100" aria-label="Shortlist">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span data-shortlist-count
                          class="<?= empty($shortlistIds) ? 'hidden ' : '' ?>absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center"><?= count($shortlistIds ?? []) ?></span>
                </a>
                <a href="<?= base_url('cart') ?>" class="relative inline-flex p-2 rounded-full hover:bg-slate-100" aria-label="Cart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h11l3-8H6.4M7 13l-1.7 5h13.4M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm10 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/></svg>
                    <span data-cart-count class="absolute -top-1 -right-1 bg-brand-500 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 origin-center"><?= esc($cart_count ?? 0) ?></span>
                </a>
                <button @click="open = !open" class="lg:hidden p-2 rounded-md hover:bg-slate-100" aria-label="Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        <!-- Search drawer -->
        <div x-show="search" x-transition x-cloak class="py-3 border-t border-slate-100" style="display:none">
            <form method="get" action="<?= base_url('shop') ?>" class="flex gap-2 max-w-2xl mx-auto">
                <input name="q" placeholder="WHAT ARE YOU LOOKING FOR?" class="flex-1 px-4 py-3 rounded-full border border-slate-200 focus:border-brand-400 focus:outline-none text-sm uppercase tracking-wider">
                <button class="px-6 py-3 rounded-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-sm">Search</button>
            </form>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="open" x-transition x-cloak class="lg:hidden border-t border-slate-100" style="display:none">
        <div class="px-4 py-3 space-y-1 font-semibold">
            <a href="<?= base_url('/') ?>" class="block py-2 text-slate-800">Home</a>
            <a href="<?= base_url('shop/arts') ?>" class="block py-2 text-slate-800">Learning Kits</a>
            <a href="<?= base_url('shop/nature') ?>" class="block py-2 text-slate-800">Nature Kits</a>
            <a href="<?= base_url('shop/accessories') ?>" class="block py-2 text-slate-800">Accessories</a>
            <a href="<?= base_url('shop/classes') ?>" class="block py-2 text-slate-800">Classes & Coaching <span class="text-[9px] font-black bg-brand-500 text-white px-1.5 py-0.5 rounded uppercase tracking-wider ml-1">New</span></a>
            <a href="<?= base_url('shop/return-gifts') ?>" class="block py-2 text-slate-800">Return Gifts</a>
            <a href="<?= base_url('account') ?>" class="block py-2 text-slate-700 border-t border-slate-100 mt-2 pt-3">My Account</a>
            <a href="<?= base_url('account/wishlist') ?>" class="block py-2 text-slate-700">Wishlist</a>
        </div>
    </div>
</header>
