<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<script type="application/ld+json"><?= $schemaJson ?></script>

<?php
$default = $product['variants'][0] ?? null;
$discount = ($default && $default['compare_at_price'] && $default['compare_at_price'] > $default['price'])
    ? round((1 - ($default['price'] / $default['compare_at_price'])) * 100)
    : 0;
$inStock = $product['total_stock'] > 0 || $product['type'] === 'digital';
?>

<?php
$pdpOpts = [
    'defaultVariantId' => (int) ($default['id'] ?? 0),
    'variants'         => array_map(fn ($v) => [
        'id'      => (int) $v['id'],
        'name'    => $v['name'],
        'price'   => (int) $v['price'],
        'compare' => (int) ($v['compare_at_price'] ?? 0),
    ], $product['variants']),
    'addToCartUrl' => base_url('cart/add'),
    'cartUrl'      => base_url('cart'),
    'checkoutUrl'  => base_url('checkout'),
    'csrfName'     => csrf_token(),
    'csrfHash'     => csrf_hash(),
    'sku'          => $product['sku'],
];
?>
<section class="py-4 sm:py-6 lg:py-10 bg-white" x-data='pdpState(<?= json_encode($pdpOpts, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <nav class="text-[11px] sm:text-xs text-slate-500 mb-3 sm:mb-4 flex flex-wrap items-center gap-x-1 gap-y-0.5">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <a href="<?= base_url('shop') ?>" class="hover:underline">Shop</a>
            <?php if (! empty($product['categories'][0])): ?>
                <span>&raquo;</span> <a href="<?= base_url('shop/' . $product['categories'][0]['slug']) ?>" class="hover:underline"><?= esc($product['categories'][0]['name']) ?></a>
            <?php endif; ?>
            <span>&raquo;</span>
            <span class="text-slate-900 font-semibold line-clamp-1 max-w-full"><?= esc($product['name']) ?></span>
        </nav>

        <div class="grid lg:grid-cols-2 gap-5 sm:gap-6 lg:gap-8">
            <!-- Gallery: swipeable carousel of images + a playable video slide + thumbnails below -->
            <?php
            $hero = $product['hero_image'] ?? null;
            $heroSrc = $hero ? (preg_match('#^https?://#', $hero) ? $hero : base_url($hero)) : null;
            $gallery = json_decode($product['gallery'] ?? '[]', true) ?: [];
            $galleryImages = array_map(fn ($g) => preg_match('#^https?://#', $g) ? $g : base_url($g), $gallery);
            $imageSlides = array_values(array_filter(array_merge([$heroSrc], $galleryImages)));

            // Compose slides — last slide is the video if present
            $slides = [];
            foreach ($imageSlides as $img) $slides[] = ['type' => 'image', 'src' => $img];
            if (! empty($product['video_url'])) $slides[] = ['type' => 'video', 'src' => $product['video_url']];
            $slideCount = count($slides);
            $altText = esc($product['name'], 'attr');
            ?>
            <div x-data='kbGallery({total: <?= $slideCount ?>})' x-init="init()" class="kb-gallery">
                <!-- Main carousel area -->
                <div class="relative group">
                    <div x-ref="track"
                         @scroll.passive="syncIndex()"
                         class="aspect-square rounded-2xl overflow-hidden bg-slate-100 flex snap-x snap-mandatory overflow-x-auto scroll-smooth touch-pan-x no-scrollbar">
                        <?php foreach ($slides as $i => $s): ?>
                            <?php if ($s['type'] === 'image'): ?>
                                <div class="snap-center shrink-0 w-full h-full">
                                    <img src="<?= esc($s['src'], 'attr') ?>" alt="<?= $altText ?>"
                                         class="w-full h-full object-cover" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" draggable="false">
                                </div>
                            <?php else: ?>
                                <div class="snap-center shrink-0 w-full h-full bg-black flex items-center justify-center relative" data-slide-video data-src="<?= esc($s['src'], 'attr') ?>">
                                    <!-- Video poster + play overlay until activated -->
                                    <div class="absolute inset-0 flex items-center justify-center cursor-pointer kb-video-poster" @click="playVideo(<?= $i ?>)">
                                        <img src="<?= esc($imageSlides[0] ?? '', 'attr') ?>" class="w-full h-full object-cover opacity-50">
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="w-20 h-20 rounded-full bg-white/95 shadow-2xl flex items-center justify-center group-hover:scale-110 transition">
                                                <svg class="w-8 h-8 text-brand-500 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                            </div>
                                        </div>
                                        <div class="absolute bottom-3 left-3 px-3 py-1 rounded-full bg-black/70 text-white text-xs font-bold uppercase tracking-wide">▶ Play video</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($slideCount > 1): ?>
                        <!-- Prev/Next arrows -->
                        <button type="button" @click="prev()"
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow-lg hover:bg-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition disabled:opacity-30"
                                :disabled="active === 0" aria-label="Previous">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <button type="button" @click="next()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow-lg hover:bg-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition disabled:opacity-30"
                                :disabled="active >= <?= $slideCount - 1 ?>" aria-label="Next">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                        </button>

                        <!-- Counter pill -->
                        <div class="absolute bottom-3 right-3 px-3 py-1 rounded-full bg-black/60 text-white text-xs font-semibold">
                            <span x-text="active + 1">1</span> / <?= $slideCount ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($slideCount > 1): ?>
                <!-- Thumbnail strip — always horizontal flex (scrolls if too many) -->
                <div class="mt-3 flex gap-2 overflow-x-auto no-scrollbar pb-1">
                    <?php foreach ($slides as $i => $s): ?>
                        <button type="button" @click="goto(<?= $i ?>)"
                                :class="active === <?= $i ?> ? 'border-brand-500 ring-2 ring-brand-200' : 'border-slate-200 hover:border-brand-300'"
                                class="relative shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-lg overflow-hidden border-2 bg-white transition">
                            <?php if ($s['type'] === 'image'): ?>
                                <img src="<?= esc($s['src'], 'attr') ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                            <?php else: ?>
                                <img src="<?= esc($imageSlides[0] ?? '', 'attr') ?>" class="w-full h-full object-cover opacity-60" loading="lazy" alt="">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-7 h-7 rounded-full bg-white/95 shadow flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-brand-500 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Buy box -->
            <div class="min-w-0">
                <?php if (! empty($product['age_min_years']) || ! empty($product['age_max_years'])): ?>
                    <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold uppercase tracking-wide">Ages <?= (int) $product['age_min_years'] ?>–<?= (int) $product['age_max_years'] ?></span>
                <?php endif; ?>
                <h1 class="mt-2 text-2xl sm:text-3xl lg:text-4xl font-black leading-tight"><?= esc($product['name']) ?></h1>

                <?php if ((float) $product['rating_avg'] > 0): ?>
                    <div class="mt-2 text-sm text-amber-500">
                        ★★★★★ <span class="text-slate-700 font-semibold ml-1"><?= number_format($product['rating_avg'], 1) ?></span>
                        <span class="text-slate-400">(<?= (int) $product['rating_count'] ?> reviews)</span>
                    </div>
                <?php endif; ?>

                <p class="mt-3 text-slate-700"><?= esc($product['short_desc']) ?></p>

                <?php
                $initPrice    = (int) ($default['price'] ?? 0);
                $initCompare  = (int) ($default['compare_at_price'] ?? 0);
                $hasDiscount  = $initCompare > $initPrice && $initPrice > 0;
                $initDiscount = $hasDiscount ? (int) round((1 - $initPrice / $initCompare) * 100) : 0;
                ?>
                <!-- Price (server-rendered, then Alpine updates on variant change) -->
                <div class="mt-4 sm:mt-5 flex items-baseline gap-3 flex-wrap">
                    <span class="text-3xl sm:text-4xl font-black" id="kb-price">
                        <span x-text="formatRupees(currentPrice)"><?= kb_money_short($initPrice) ?></span>
                    </span>
                    <?php if ($hasDiscount): ?>
                        <span x-show="currentCompare > currentPrice" class="contents">
                            <span class="text-xl text-slate-400 line-through" x-text="formatRupees(currentCompare)"><?= kb_money_short($initCompare) ?></span>
                            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 text-xs font-bold" x-text="discountPct + '% off'"><?= $initDiscount ?>% off</span>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    Inclusive of all taxes. Earn
                    <span class="font-semibold text-slate-700" x-text="Math.floor(currentPrice/100) + ' Khoobie points'"><?= number_format((int) floor($initPrice / 100)) ?> Khoobie points</span>
                    on this purchase.
                </p>

                <!-- Source / fulfilment label — "Shipped by Khoobie" / "Sold by Partner" / "Instant download" etc. -->
                <?= $this->include('partials/_product_source') ?>

                <?php if (count($product['variants']) > 1): ?>
                    <div class="mt-5">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-700">Choose option</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <?php foreach ($product['variants'] as $v): ?>
                                <button @click="select(<?= (int) $v['id'] ?>)"
                                        :class="currentId === <?= (int) $v['id'] ?> ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-700'"
                                        class="px-3 py-2 rounded-lg border text-sm font-semibold hover:border-brand-400">
                                    <?= esc($v['name']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                // Stock badge is only meaningful for PHYSICAL types.
                // Courses, tuitions, meetups, services, memberships, digital and affiliate
                // never have "stock" in the inventory sense — showing "Out of stock" on a
                // digital course is a UX bug that kills conversions.
                $physicalTypes = ['simple', 'variable', 'bundle'];
                if (in_array($product['type'], $physicalTypes, true)):
                ?>
                    <div class="mt-5">
                        <?php if ($inStock): ?>
                            <span class="inline-flex items-center gap-1 text-sm text-emerald-600 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                In stock <?= $product['total_stock'] > 0 && $product['total_stock'] <= 10 ? ' — only ' . (int) $product['total_stock'] . ' left' : '' ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 text-sm text-amber-600 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                Out of stock — notify me when available
                            </span>
                        <?php endif; ?>
                    </div>
                <?php elseif (in_array($product['type'], ['meetup','workshop','camp'], true) && $product['total_stock'] > 0 && $product['total_stock'] <= 5): ?>
                    <!-- Seat-capacity urgency for in-person events (meetups limit seats, not inventory) -->
                    <div class="mt-5">
                        <span class="inline-flex items-center gap-1 text-sm text-rose-600 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Only <?= (int) $product['total_stock'] ?> seats left — fills up fast
                        </span>
                    </div>
                <?php endif; ?>

                <?= $this->include('partials/_pdp_cta') ?>

                <!-- Trust block — recent demand, stock urgency, verified reviews, instructor cred -->
                <?= $this->include('partials/_pdp_trust') ?>

                <!-- Type-aware intent capture (trial / RSVP / seat-block / discovery call / notify-me).
                     Renders nothing for cart-only types (simple/variable/bundle/affiliate). -->
                <?= $this->include('partials/_pdp_intent') ?>

                <?php
                // ─── Pincode delivery check ─────────────────────────────────────
                // Only physical types actually ship. Showing "Estimate delivery" on a
                // digital course / online class / video meetup is irrelevant and confusing.
                if (in_array($product['type'], ['simple','variable','bundle'], true)):
                ?>
                    <div class="mt-5 border border-slate-200 rounded-xl p-4" x-data="{ pin: '', zone: null }">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-700">Estimate delivery</div>
                        <div class="mt-2 flex gap-2">
                            <input x-model="pin" maxlength="6" inputmode="numeric" placeholder="Enter pincode" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm">
                            <button type="button" @click="
                                if (!/^\d{6}$/.test(pin)) { zone = { err: 'Enter a valid 6-digit pincode' }; return }
                                const d = parseInt(pin[0]);
                                const map = {1:['North','2–4'],2:['North','2–4'],3:['West (Gujarat)','3–5'],4:['West','3–5'],5:['South Central','3–6'],6:['South','4–6'],7:['East','4–7'],8:['East / NE','5–8'],9:['NE / Forces','6–10']};
                                const z = map[d] || ['India','3–6'];
                                zone = { zone: z[0], days: z[1] };
                            " class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Check</button>
                        </div>
                        <template x-if="zone && zone.err">
                            <p class="mt-2 text-xs text-rose-600" x-text="zone.err"></p>
                        </template>
                        <template x-if="zone && zone.days">
                            <p class="mt-2 text-xs text-emerald-700">
                                ✓ <span x-text="zone.zone"></span> · Delivery in <strong x-text="zone.days"></strong> business days · COD available
                            </p>
                        </template>
                    </div>
                <?php endif; ?>

                <?php
                // ─── Type-aware trust badges ────────────────────────────────────
                // Each product type earns trust differently. Showing "Free shipping ₹999+"
                // on a digital course or "Handmade in India" on an online tuition is jarring
                // and breaks credibility. Per-type sets that actually mean something:
                $trustBadges = match ($product['type']) {
                    'digital'    => [
                        ['✓', 'Instant download'],     ['✓', 'Lifetime access'],
                        ['✓', 'Multi-device — phone/tablet/laptop'],  ['✓', '7-day refund if file unusable'],
                    ],
                    'course'     => [
                        ['✓', 'Lifetime access · learn at your pace'], ['✓', 'Khoobie-vetted instructor'],
                        ['✓', 'Certificate on completion'],            ['✓', '7-day refund if not satisfied'],
                    ],
                    'tuition'    => [
                        ['✓', 'FREE trial · cancel anytime'],          ['✓', 'Background-checked instructor'],
                        ['✓', 'Small batch · 4-8 kids'],                ['✓', 'Recording if you miss a class'],
                    ],
                    'meetup', 'workshop', 'camp' => [
                        ['✓', 'Venue map + timing on WhatsApp'],        ['✓', 'Refundable till 48h before'],
                        ['✓', 'Seat guaranteed once paid'],             ['✓', 'Khoobie host on site'],
                    ],
                    'service'    => [
                        ['✓', 'Verified provider'],                     ['✓', 'Pick your own slot'],
                        ['✓', 'Free reschedule up to 24h before'],      ['✓', 'Written summary after session'],
                    ],
                    'membership' => [
                        ['✓', '7-day FREE trial'],                      ['✓', 'Cancel anytime · no lock-in'],
                        ['✓', 'All perks unlock instantly'],            ['✓', 'Bonus Khoobie points monthly'],
                    ],
                    'affiliate'  => [
                        ['✓', 'Khoobie hand-picked'],                   ['✓', 'You check out on the marketplace'],
                        ['✓', 'Returns &amp; warranty by seller'],        ['✓', 'No extra charge — same price'],
                    ],
                    default      => [
                        ['✓', 'Free shipping over ' . kb_money_short(99900)], ['✓', 'COD available'],
                        ['✓', '7-day returns'],                                 ['✓', 'Handmade in India'],
                    ],
                };
                ?>
                <div class="mt-6 grid grid-cols-2 gap-3 text-xs">
                    <?php foreach ($trustBadges as $b): ?>
                        <div class="flex items-center gap-2 text-slate-600">
                            <span class="text-emerald-500 text-base"><?= $b[0] ?></span>
                            <span><?= $b[1] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Share -->
                <div class="mt-5 pt-5 border-t border-slate-200">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">Share this product</div>
                    <div class="flex gap-2">
                        <?php
                        $shareUrl   = urlencode(current_url());
                        $shareTitle = urlencode($product['name']);
                        ?>
                        <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" class="px-3 py-1.5 rounded-full bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-semibold">WhatsApp</a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" class="px-3 py-1.5 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs font-semibold">Facebook</a>
                        <a href="https://twitter.com/intent/tweet?text=<?= $shareTitle ?>&url=<?= $shareUrl ?>" target="_blank" class="px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold">X / Twitter</a>
                        <button onclick="navigator.clipboard.writeText(location.href);this.textContent='Copied!'" class="px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold">Copy link</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Sticky bottom action bar — always visible on mobile + desktop -->
    <?= $this->include('partials/_pdp_sticky_cart') ?>
</section>

<!-- A+ Rich blocks -->
<?php
$richBlocks = json_decode($product['rich_blocks'] ?? '[]', true) ?: [];
foreach ($richBlocks as $block):
    if ($block['type'] === 'usp_grid'):
?>
    <section class="py-10 bg-slate-50">
        <div class="mx-auto max-w-5xl px-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($block['items'] as $item): ?>
                <div class="bg-white rounded-2xl p-5 text-center">
                    <div class="text-3xl"><?= esc($item['icon']) ?></div>
                    <div class="mt-2 font-bold text-sm"><?= esc($item['title']) ?></div>
                    <div class="mt-1 text-xs text-slate-500"><?= esc($item['desc']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php
    elseif ($block['type'] === 'video' && !empty($block['url'])):
?>
    <section class="py-10">
        <div class="mx-auto max-w-4xl px-4">
            <h2 class="text-2xl font-black text-center"><?= esc($block['caption'] ?? 'Watch it in action') ?></h2>
            <div class="mt-4 aspect-video rounded-2xl overflow-hidden shadow-lg">
                <iframe src="<?= esc($block['url']) ?>" allowfullscreen class="w-full h-full" frameborder="0" loading="lazy"></iframe>
            </div>
        </div>
    </section>
<?php
    elseif ($block['type'] === 'faq'):
?>
    <section class="py-10 bg-slate-50">
        <div class="mx-auto max-w-3xl px-4">
            <h2 class="text-2xl font-black">Frequently asked</h2>
            <div class="mt-4 space-y-3" x-data="{ open: null }">
                <?php foreach ($block['items'] as $i => $faq): ?>
                <div class="bg-white rounded-xl border border-slate-100">
                    <button type="button" @click="open = open === <?= $i ?> ? null : <?= $i ?>" class="w-full flex items-center justify-between px-4 py-3 text-left">
                        <span class="font-semibold text-sm"><?= esc($faq['q']) ?></span>
                        <span class="text-slate-400" x-text="open === <?= $i ?> ? '−' : '+'"></span>
                    </button>
                    <div x-show="open === <?= $i ?>" x-collapse class="px-4 pb-3 text-sm text-slate-600"><?= esc($faq['a']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php
    endif;
endforeach;
?>

<?php if (! empty($product['long_desc'])): ?>
<section class="py-10">
    <div class="mx-auto max-w-4xl px-4">
        <h2 class="text-2xl font-black">About this product</h2>
        <div class="mt-3 prose prose-slate max-w-none text-slate-700">
            <?= kb_safe_html($product['long_desc']) ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Specs / Attributes -->
<?php if (! empty($product['attributes'])): ?>
<section class="py-10">
    <div class="mx-auto max-w-4xl px-4">
        <h2 class="text-2xl font-black">Specifications</h2>
        <dl class="mt-4 grid sm:grid-cols-2 gap-x-8 gap-y-2 text-sm">
            <?php foreach ($product['attributes'] as $a): ?>
                <div class="flex justify-between border-b border-slate-100 py-2">
                    <dt class="text-slate-500"><?= esc($a['key']) ?></dt>
                    <dd class="font-semibold"><?= esc($a['value']) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
<?php endif; ?>

<!-- Reviews -->
<section class="py-10 bg-slate-50">
    <div class="mx-auto max-w-4xl px-4">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-black">What parents say</h2>
                <?php if ((float) $product['rating_avg'] > 0): ?>
                    <div class="mt-1 text-sm text-amber-500">
                        ★★★★★ <span class="text-slate-700 font-bold"><?= number_format((float) $product['rating_avg'], 1) ?>/5</span>
                        <span class="text-slate-500">from <?= (int) $product['rating_count'] ?> reviews</span>
                    </div>
                <?php endif; ?>
            </div>
            <button x-data @click="document.getElementById('write-review').scrollIntoView({behavior:'smooth'})" class="btn-ghost text-sm py-2 px-4">Write a review · earn 50 pts</button>
        </div>

        <?php if (empty($product['reviews'])): ?>
            <div class="mt-4 bg-white rounded-xl p-6 text-center text-sm text-slate-600">
                No reviews yet — <a href="#write-review" class="text-brand-600 font-semibold hover:underline">be the first to share yours</a> and earn 50 Khoobie Points!
            </div>
        <?php else: ?>
            <div class="mt-4 space-y-4">
                <?php foreach ($product['reviews'] as $r): ?>
                    <div class="bg-white p-4 rounded-xl">
                        <div class="flex items-center gap-2">
                            <span class="text-amber-500"><?= str_repeat('★', (int) $r['rating']) ?><span class="text-slate-200"><?= str_repeat('★', 5 - (int) $r['rating']) ?></span></span>
                            <span class="text-sm font-semibold"><?= esc($r['reviewer_name']) ?></span>
                            <?php if ($r['is_verified_buyer']): ?><span class="text-[10px] uppercase tracking-wide bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Verified buyer</span><?php endif; ?>
                            <span class="text-xs text-slate-400 ml-auto"><?= kb_relative($r['created_at']) ?></span>
                        </div>
                        <?php if ($r['title']): ?><div class="mt-1 font-bold text-sm"><?= esc($r['title']) ?></div><?php endif; ?>
                        <p class="mt-1 text-sm text-slate-700"><?= esc($r['body']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Write a review form -->
        <div id="write-review" class="mt-8 bg-white rounded-2xl p-6">
            <h3 class="font-bold">Write a review</h3>
            <p class="mt-1 text-xs text-slate-500">Earn 50 Khoobie Points when your review is published.</p>
            <form method="post" action="<?= base_url('product/' . $product['slug'] . '/review') ?>" class="mt-4 space-y-3" x-data="{ rating: 5 }">
                <?= csrf_field() ?>
                <?= $this->include('partials/_honeypot') ?>
                <div class="flex items-center gap-1 text-2xl">
                    <template x-for="i in 5" :key="i">
                        <button type="button" @click="rating = i" :class="i <= rating ? 'text-amber-500' : 'text-slate-200'" class="hover:scale-110 transition">★</button>
                    </template>
                    <input type="hidden" name="rating" x-model="rating">
                </div>
                <input name="reviewer_name" required placeholder="Your name" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                <input name="title" placeholder="Title (optional)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                <textarea name="body" required rows="4" placeholder="What did you and your child love about it?" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea>
                <button class="btn-primary text-sm">Submit review</button>
                <p class="text-[11px] text-slate-400">Reviews are moderated and usually appear within 24 hours.</p>
            </form>
        </div>
    </div>
</section>

<!-- Bundles — kit + class flywheel made explicit -->
<?= $this->include('partials/_pdp_bundles') ?>

<!-- Related / Upsells -->
<?php if (! empty($product['related'])): ?>
<section class="py-8 sm:py-10 bg-white">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <div>
            <span class="text-xs uppercase tracking-wider font-bold text-brand-600">✨ You may also like</span>
            <h2 class="mt-1 text-xl sm:text-2xl font-black">More from this collection</h2>
        </div>
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php foreach (array_slice($product['related'], 0, 4) as $rel): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', [
                    'p'            => $rel,
                    'cartVariants' => $cartVariants ?? [],
                    'shortlistIds' => $shortlistIds ?? [],
                    'compareIds'   => $compareIds   ?? [],
                ]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Product Q&A -->
<?= $this->include('partials/_pdp_qa') ?>

<!-- Recently viewed (session-tracked, excludes current product) -->
<?= view('partials/_recently_viewed', [
    'items'        => $recentlyViewed ?? [],
    'cartVariants' => $cartVariants ?? [],
    'bg'           => 'bg-slate-50',
]) ?>

<?= $this->endSection() ?>
