<?php
/**
 * Mega menu — the proper entry point for each of Khoobie's 6 product lines.
 * Each top-level item opens a hover panel (desktop) or expands inline (mobile).
 *
 * Driven by hardcoded structure here (fast, no DB hit per request) — change
 * once when categories evolve.
 */
?>
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-100" x-data="{ open: false, search: false, panel: null }" @mouseleave="panel = null">

    <!-- Announcement bar -->
    <div class="bg-slate-900 text-white text-xs sm:text-sm py-2 text-center px-4">
        <span class="hidden sm:inline">🎁 FREE shipping on orders ₹999+ • COD across India • </span>
        <span>Use code <b>WELCOME10</b> for 10% off your first order</span>
    </div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4 h-16 lg:h-20">
            <!-- Logo + sticky location pill -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <a href="<?= base_url('/') ?>" class="shrink-0">
                    <img src="<?= base_url('assets/brand/logo.png') ?>" alt="<?= esc($brand['name']) ?>" class="h-10 lg:h-12 w-auto">
                </a>
                <button @click="$dispatch('open-location-picker')"
                        class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-slate-100 hover:bg-brand-100 text-slate-700 hover:text-brand-700 text-xs font-bold transition group">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="max-w-[140px] truncate">
                        <?= ! empty($location_label) ? esc($location_label) : 'Set your city' ?>
                    </span>
                    <svg class="w-3 h-3 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </div>

            <!-- Desktop mega nav -->
            <nav class="hidden lg:flex items-center gap-1 text-sm font-semibold text-slate-800 flex-1 justify-center">
                <!-- HOME -->
                <a href="<?= base_url('/') ?>" class="px-3 py-2 rounded-lg hover:text-brand-600 transition">Home</a>

                <!-- KITS & PRODUCTS -->
                <div @mouseenter="panel = 'shop'" class="relative">
                    <button class="px-3 py-2 rounded-lg hover:text-brand-600 transition flex items-center gap-1"
                            :class="panel === 'shop' ? 'text-brand-600' : ''">
                        Kits & Products
                        <svg class="w-3 h-3 transition" :class="panel === 'shop' ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                </div>

                <!-- CLASSES & EXPERIENCES -->
                <div @mouseenter="panel = 'classes'" class="relative">
                    <button class="px-3 py-2 rounded-lg hover:text-brand-600 transition flex items-center gap-1"
                            :class="panel === 'classes' ? 'text-brand-600' : ''">
                        Classes & Experiences
                        <span class="text-[9px] font-black bg-brand-500 text-white px-1.5 py-0.5 rounded uppercase tracking-wider">New</span>
                        <svg class="w-3 h-3 transition" :class="panel === 'classes' ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                </div>

                <!-- DIGITAL DOWNLOADS -->
                <a href="<?= base_url('digital') ?>" @mouseenter="panel = 'digital'" class="px-3 py-2 rounded-lg hover:text-brand-600 transition">Digital</a>

                <!-- EDITOR'S PICKS -->
                <a href="<?= base_url('affiliate') ?>" @mouseenter="panel = null" class="px-3 py-2 rounded-lg hover:text-brand-600 transition">Editor's Picks</a>

                <!-- BLOG -->
                <a href="<?= base_url('blog') ?>" @mouseenter="panel = null" class="px-3 py-2 rounded-lg hover:text-brand-600 transition">Blog</a>
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
                    <span data-compare-count class="<?= empty($compareIds) ? 'hidden ' : '' ?>absolute -top-1 -right-1 bg-sky-600 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center"><?= count($compareIds ?? []) ?></span>
                </a>
                <a href="<?= base_url('shortlist') ?>" class="relative hidden sm:inline-flex p-2 rounded-full hover:bg-slate-100" aria-label="Shortlist">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span data-shortlist-count class="<?= empty($shortlistIds) ? 'hidden ' : '' ?>absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center"><?= count($shortlistIds ?? []) ?></span>
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
                <input name="q" placeholder="What are you looking for?" class="flex-1 px-4 py-3 rounded-full border border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
                <button class="px-6 py-3 rounded-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-sm">Search</button>
            </form>
        </div>
    </div>

    <!-- ============ MEGA PANELS (desktop) ============ -->
    <div class="hidden lg:block">

        <!-- SHOP panel -->
        <div x-show="panel === 'shop'" x-transition x-cloak class="absolute left-0 right-0 bg-white border-t border-slate-100 shadow-soft-lg" @mouseleave="panel = null" style="display:none">
            <div class="mx-auto max-w-7xl px-6 lg:px-8 py-6 grid grid-cols-4 gap-8">
                <div>
                    <div class="eyebrow text-brand-600 mb-3">Shop by category</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= base_url('shop/arts') ?>" class="block py-1 hover:text-brand-600 font-semibold">🎨 Learning Kits</a></li>
                        <li><a href="<?= base_url('shop/nature') ?>" class="block py-1 hover:text-brand-600 font-semibold">🌱 Nature Kits</a></li>
                        <li><a href="<?= base_url('shop/accessories') ?>" class="block py-1 hover:text-brand-600 font-semibold">✏️ Accessories</a></li>
                        <li><a href="<?= base_url('shop/return-gifts') ?>" class="block py-1 hover:text-brand-600 font-semibold">🎁 Return Gifts</a></li>
                        <li class="pt-1"><a href="<?= base_url('shop') ?>" class="text-xs text-brand-600 font-bold hover:underline">All products →</a></li>
                    </ul>
                </div>
                <div>
                    <div class="eyebrow text-violet-600 mb-3">Shop by age</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= base_url('shop?age_min=2&age_max=4') ?>" class="block py-1 hover:text-violet-600 font-semibold">👶 2–4 years (Toddler)</a></li>
                        <li><a href="<?= base_url('shop?age_min=4&age_max=7') ?>" class="block py-1 hover:text-violet-600 font-semibold">🧒 4–7 years (Early)</a></li>
                        <li><a href="<?= base_url('shop?age_min=7&age_max=10') ?>" class="block py-1 hover:text-violet-600 font-semibold">👧 7–10 years (Junior)</a></li>
                        <li><a href="<?= base_url('shop?age_min=10&age_max=14') ?>" class="block py-1 hover:text-violet-600 font-semibold">🧑 10–14 years (Pre-teen)</a></li>
                    </ul>
                </div>
                <div>
                    <div class="eyebrow text-amber-600 mb-3">Save more</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= base_url('shop?sort=bestselling') ?>" class="block py-1 hover:text-amber-600 font-semibold">🔥 Bestsellers</a></li>
                        <li><a href="<?= base_url('shop?sort=newest') ?>" class="block py-1 hover:text-amber-600 font-semibold">✨ New arrivals</a></li>
                        <li><a href="<?= base_url('shop?sort=rating') ?>" class="block py-1 hover:text-amber-600 font-semibold">⭐ Top rated</a></li>
                        <li><a href="<?= base_url('bundle/pottery-kit-and-class') ?>" class="block py-1 hover:text-amber-600 font-semibold">📦 Kit + Class bundles</a></li>
                    </ul>
                </div>
                <!-- Featured visual block -->
                <div class="bg-gradient-to-br from-brand-500 via-rose-500 to-amber-500 rounded-2xl p-5 text-white">
                    <div class="eyebrow text-white/80 mb-2">🎁 Featured</div>
                    <div class="font-display text-xl font-black leading-tight">Pottery Starter Bundle</div>
                    <p class="text-xs mt-1 opacity-90">Clay kit + 4-week online pottery class</p>
                    <div class="mt-2 text-lg font-black">₹2,440 <span class="text-xs line-through opacity-70">₹3,050</span></div>
                    <a href="<?= base_url('bundle/pottery-kit-and-class') ?>" class="mt-3 inline-block px-3 py-1.5 rounded-full bg-white text-slate-900 text-xs font-bold">Save ₹610 →</a>
                </div>
            </div>
        </div>

        <!-- CLASSES panel -->
        <div x-show="panel === 'classes'" x-transition x-cloak class="absolute left-0 right-0 bg-white border-t border-slate-100 shadow-soft-lg" @mouseleave="panel = null" style="display:none">
            <div class="mx-auto max-w-7xl px-6 lg:px-8 py-6 grid grid-cols-4 gap-8">
                <div>
                    <div class="eyebrow text-violet-600 mb-3">💻 Live online</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= base_url('shop/mindsport-classes') ?>" class="block py-1 hover:text-violet-600 font-semibold">♟️ Chess · Cubing · Maths</a></li>
                        <li><a href="<?= base_url('shop/creative-classes') ?>" class="block py-1 hover:text-violet-600 font-semibold">🎨 Mandala · Calligraphy · Pottery</a></li>
                        <li><a href="<?= base_url('shop/activity-classes') ?>" class="block py-1 hover:text-violet-600 font-semibold">🎤 Public speaking · Yoga · Music</a></li>
                        <li class="pt-1"><a href="<?= base_url('classes') ?>" class="text-xs text-violet-600 font-bold hover:underline">All classes →</a></li>
                    </ul>
                </div>
                <div>
                    <div class="eyebrow text-amber-600 mb-3">📍 In-person near you</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= base_url('shop/local-meetups?city=Mumbai') ?>" class="block py-1 hover:text-amber-600 font-semibold">📍 Mumbai</a></li>
                        <li><a href="<?= base_url('shop/local-meetups?city=Bangalore') ?>" class="block py-1 hover:text-amber-600 font-semibold">📍 Bangalore</a></li>
                        <li><a href="<?= base_url('shop/local-meetups?city=Delhi') ?>" class="block py-1 hover:text-amber-600 font-semibold">📍 Delhi · NCR</a></li>
                        <li><a href="<?= base_url('shop/local-meetups?city=Pune') ?>" class="block py-1 hover:text-amber-600 font-semibold">📍 Pune</a></li>
                        <li class="pt-1"><a href="<?= base_url('meetups') ?>" class="text-xs text-amber-600 font-bold hover:underline">All cities →</a></li>
                    </ul>
                </div>
                <div>
                    <div class="eyebrow text-emerald-600 mb-3">🤝 1-on-1 & membership</div>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= base_url('shop?type=service') ?>" class="block py-1 hover:text-emerald-600 font-semibold">☎️ Discovery calls</a></li>
                        <li><a href="<?= base_url('shop?type=service') ?>" class="block py-1 hover:text-emerald-600 font-semibold">🎓 Private tutoring</a></li>
                        <li><a href="<?= base_url('shop?type=membership') ?>" class="block py-1 hover:text-emerald-600 font-semibold">⭐ Khoobie Insider</a></li>
                        <li><a href="<?= base_url('shop?type=meetup&free=1') ?>" class="block py-1 hover:text-emerald-600 font-semibold">🎟️ Free RSVPs</a></li>
                    </ul>
                </div>
                <!-- Featured class card -->
                <div class="bg-gradient-to-br from-violet-500 via-sky-500 to-emerald-500 rounded-2xl p-5 text-white">
                    <div class="eyebrow text-white/80 mb-2">🎓 Trial class</div>
                    <div class="font-display text-xl font-black leading-tight">Free chess trial</div>
                    <p class="text-xs mt-1 opacity-90">For ages 7+ · with FIDE-rated coach · no card needed</p>
                    <a href="<?= base_url('shop/mindsport-classes') ?>" class="mt-3 inline-block px-3 py-1.5 rounded-full bg-white text-slate-900 text-xs font-bold">Book free trial →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ MOBILE DRAWER ============ -->
    <div x-show="open" x-transition x-cloak class="lg:hidden border-t border-slate-100 max-h-[80vh] overflow-y-auto" style="display:none">
        <div class="px-4 py-3 space-y-1 font-semibold">
            <a href="<?= base_url('/') ?>" class="block py-2 text-slate-800">🏠 Home</a>

            <details class="py-1 border-t border-slate-100">
                <summary class="py-2 cursor-pointer flex items-center justify-between">🎨 Kits & Products <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></summary>
                <ul class="ml-3 mt-1 space-y-1 text-sm">
                    <li><a href="<?= base_url('shop/arts') ?>" class="block py-1.5">Learning Kits</a></li>
                    <li><a href="<?= base_url('shop/nature') ?>" class="block py-1.5">Nature Kits</a></li>
                    <li><a href="<?= base_url('shop/accessories') ?>" class="block py-1.5">Accessories</a></li>
                    <li><a href="<?= base_url('shop/return-gifts') ?>" class="block py-1.5">Return Gifts</a></li>
                    <li><a href="<?= base_url('shop') ?>" class="block py-1.5 text-brand-600 font-bold">All products →</a></li>
                </ul>
            </details>

            <details class="py-1 border-t border-slate-100">
                <summary class="py-2 cursor-pointer flex items-center justify-between">🎓 Classes & Experiences <span class="text-[9px] font-black bg-brand-500 text-white px-1.5 py-0.5 rounded uppercase tracking-wider">New</span></summary>
                <ul class="ml-3 mt-1 space-y-1 text-sm">
                    <li><a href="<?= base_url('shop/mindsport-classes') ?>" class="block py-1.5">♟️ Mind sports</a></li>
                    <li><a href="<?= base_url('shop/creative-classes') ?>" class="block py-1.5">🎨 Creative</a></li>
                    <li><a href="<?= base_url('shop/activity-classes') ?>" class="block py-1.5">🎤 Activity & confidence</a></li>
                    <li><a href="<?= base_url('shop/local-meetups') ?>" class="block py-1.5">📍 In-person near me</a></li>
                    <li><a href="<?= base_url('classes') ?>" class="block py-1.5 text-violet-600 font-bold">All classes →</a></li>
                </ul>
            </details>

            <a href="<?= base_url('digital') ?>"   class="block py-2 border-t border-slate-100">💾 Digital Downloads</a>
            <a href="<?= base_url('affiliate') ?>" class="block py-2 border-t border-slate-100">⭐ Editor's Picks</a>
            <a href="<?= base_url('blog') ?>"      class="block py-2 border-t border-slate-100">📖 Blog</a>

            <div class="border-t border-slate-100 pt-3 mt-3">
                <a href="<?= base_url('account') ?>"           class="block py-2 text-slate-700">My Account</a>
                <a href="<?= base_url('shortlist') ?>"         class="block py-2 text-slate-700">Shortlist</a>
                <a href="<?= base_url('compare') ?>"           class="block py-2 text-slate-700">Compare</a>
                <a href="<?= base_url('account/referrals') ?>" class="block py-2 text-slate-700">Refer & Earn</a>
                <a href="<?= base_url('sell-with-khoobie') ?>" class="block py-2 text-emerald-700 font-bold">🤝 Sell with Khoobie</a>
            </div>
        </div>
    </div>
</header>
