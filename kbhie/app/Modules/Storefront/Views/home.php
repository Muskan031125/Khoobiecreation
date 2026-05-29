<?= $this->extend('layouts/master') ?>

<?= $this->section('content') ?>

<!-- ======================================================== -->
<!-- HERO — compact, leaves room for content above the fold   -->
<!-- ======================================================== -->
<section class="relative overflow-hidden bg-gradient-to-br from-rose-50 via-amber-50 to-emerald-50">
    <div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-rose-200/40 blur-3xl"></div>
    <div class="absolute -bottom-20 -left-20 w-64 h-64 rounded-full bg-emerald-200/40 blur-3xl"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 lg:py-10 grid lg:grid-cols-[1.1fr_1fr] gap-6 lg:gap-10 items-center">
        <div>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/80 backdrop-blur text-brand-700 text-[11px] font-bold tracking-wide uppercase shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                Handmade in India · Loved by 2,300+ parents
            </span>
            <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-[1.05]">
                Hands-on. Heart-led.
                <span class="bg-gradient-to-r from-brand-500 to-rose-500 bg-clip-text text-transparent">Screen-free.</span>
            </h1>
            <p class="mt-3 text-sm sm:text-base text-slate-700 max-w-md leading-relaxed">
                From <strong>DIY craft kits</strong> to <strong>live online classes</strong>, courses, return gifts &amp; curated picks — everything your curious kid needs.
            </p>
            <div class="mt-4 flex flex-wrap gap-2.5">
                <a href="<?= base_url('shop') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-sm shadow-cta hover:shadow-xl hover:scale-105 transition">
                    Shop kits &rarr;
                </a>
                <a href="#courses" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-white hover:bg-slate-50 text-slate-900 font-semibold text-sm border-2 border-slate-200 transition">
                    Browse courses 🎓
                </a>
            </div>
            <div class="mt-5 grid grid-cols-3 max-w-md gap-3">
                <div>
                    <div class="text-xl font-black text-slate-900"><?= number_format($stats['products']) ?>+</div>
                    <div class="text-[10px] uppercase tracking-wide text-slate-500 font-semibold">Products</div>
                </div>
                <div>
                    <div class="text-xl font-black text-amber-500">★ <?= number_format($stats['avg_rating'], 1) ?></div>
                    <div class="text-[10px] uppercase tracking-wide text-slate-500 font-semibold"><?= number_format($stats['review_count']) ?> reviews</div>
                </div>
                <div>
                    <div class="text-xl font-black text-slate-900"><?= number_format($stats['customers']) ?>+</div>
                    <div class="text-[10px] uppercase tracking-wide text-slate-500 font-semibold">Happy parents</div>
                </div>
            </div>
        </div>

        <!-- Compact hero collage — smaller, denser -->
        <div class="relative h-[260px] sm:h-[280px] lg:h-[340px]">
            <div class="absolute top-0 right-0 w-40 h-40 lg:w-52 lg:h-52 rounded-3xl overflow-hidden shadow-2xl transform rotate-3 hover:rotate-0 transition duration-500">
                <img src="https://picsum.photos/seed/khoobie-hero-1/500/500" class="w-full h-full object-cover" alt="">
            </div>
            <div class="absolute bottom-8 right-24 lg:right-32 w-32 h-32 lg:w-44 lg:h-44 rounded-3xl overflow-hidden shadow-2xl transform -rotate-6 hover:rotate-0 transition duration-500 z-10">
                <img src="https://picsum.photos/seed/khoobie-hero-2/500/500" class="w-full h-full object-cover" alt="">
            </div>
            <div class="absolute bottom-0 left-0 w-36 h-36 lg:w-48 lg:h-48 rounded-3xl overflow-hidden shadow-2xl transform rotate-6 hover:rotate-0 transition duration-500">
                <img src="https://picsum.photos/seed/khoobie-hero-3/500/500" class="w-full h-full object-cover" alt="">
            </div>
            <div class="absolute top-16 left-2 lg:top-20 lg:left-8 w-28 h-28 lg:w-40 lg:h-40 rounded-3xl overflow-hidden shadow-2xl transform -rotate-3 hover:rotate-0 transition duration-500 z-10">
                <img src="https://picsum.photos/seed/khoobie-hero-4/500/500" class="w-full h-full object-cover" alt="">
            </div>
            <div class="absolute bottom-2 right-2 bg-white rounded-xl shadow-xl px-3 py-2 flex items-center gap-2 z-20 animate-bounce" style="animation-duration:3s">
                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm">✓</div>
                <div class="text-[10px]"><div class="font-bold leading-none">COD pan India</div></div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================== -->
<!-- CATEGORY MOSAIC — tightened padding for above-the-fold   -->
<!-- ======================================================== -->
<section class="bg-white py-6 lg:py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-baseline justify-between">
            <div>
                <h2 class="text-2xl sm:text-3xl font-black">Shop the collection</h2>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-500">Pick a vibe — everything is hand-curated.</p>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php
            $catMeta = [
                'arts'         => ['emoji'=>'🎨', 'seed'=>'cat-arts'],
                'nature'       => ['emoji'=>'🌱', 'seed'=>'cat-nature'],
                'accessories'  => ['emoji'=>'🎒', 'seed'=>'cat-acc'],
                'return-gifts' => ['emoji'=>'🎁', 'seed'=>'cat-gifts'],
            ];
            foreach ($categories as $cat):
                $m = $catMeta[$cat['slug']] ?? ['emoji'=>$cat['icon'] ?: '🎁','seed'=>'cat-' . $cat['slug']];
            ?>
            <a href="<?= base_url('shop/' . $cat['slug']) ?>" class="group block relative aspect-[5/4] sm:aspect-[4/3] rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition">
                <img src="https://picsum.photos/seed/<?= esc($m['seed']) ?>/600/600" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition duration-700" alt="">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 p-4 lg:p-5 text-white">
                    <div class="text-3xl"><?= $m['emoji'] ?></div>
                    <div class="mt-1 text-lg lg:text-xl font-black"><?= esc($cat['name']) ?></div>
                    <div class="mt-1 text-xs opacity-90 inline-flex items-center gap-1">Shop now <span class="group-hover:translate-x-1 transition">→</span></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ======================================================== -->
<!-- NEAR YOU IN {CITY} — only renders if user has set location -->
<!-- ======================================================== -->
<?php if (! empty($nearYou) && ! empty($location)): ?>
<section class="bg-gradient-to-br from-amber-50 via-rose-50 to-violet-50 py-8 sm:py-12 border-y border-amber-100">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <div>
                <span class="eyebrow text-amber-700">📍 Near you</span>
                <h2 class="h-display text-2xl sm:text-3xl mt-1 text-slate-900">Upcoming in <?= esc($location['city']) ?></h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">Live meetups + workshops in your city · book your seat in seconds</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= base_url('shop/local-meetups?city=' . urlencode($location['city'])) ?>" class="text-xs font-bold text-amber-700 hover:underline">See all in <?= esc($location['city']) ?> →</a>
                <button @click="$dispatch('open-location-picker')" class="text-xs font-bold text-slate-500 hover:text-brand-600">Change city</button>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php foreach ($nearYou as $p): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p, 'cartVariants'=>$cartVariants??[], 'shortlistIds'=>$shortlistIds??[], 'compareIds'=>$compareIds??[]]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php elseif (empty($location)): ?>
<!-- Set-your-location nudge if not set yet -->
<section class="bg-gradient-to-r from-violet-500 via-rose-500 to-amber-500 py-3 text-white">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6 flex items-center justify-between gap-3 flex-wrap">
        <p class="text-sm font-semibold">📍 <strong>Set your city</strong> to see weekend workshops + in-person classes near you.</p>
        <button @click="$dispatch('open-location-picker')" class="px-3 py-1.5 rounded-full bg-white text-slate-900 text-xs font-bold hover:bg-slate-100 transition">Pick my city →</button>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- RECENTLY VIEWED — only renders for returning visitors    -->
<!-- ======================================================== -->
<?= view('partials/_recently_viewed', [
    'items'        => $recentlyViewed ?? [],
    'cartVariants' => $cartVariants ?? [],
    'bg'           => 'bg-white',
]) ?>

<!-- ======================================================== -->
<!-- SHOP BY AGE — cross-category mix filtered by age range   -->
<!-- ======================================================== -->
<section class="bg-gradient-to-b from-white to-slate-50 py-8 lg:py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-baseline justify-between flex-wrap gap-2">
            <div>
                <span class="text-[11px] uppercase tracking-wider font-bold text-violet-600">👶 By age</span>
                <h2 class="mt-1 text-2xl sm:text-3xl font-black">Shop by your child's age</h2>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-500">A mixed bag of kits, books, classes &amp; more — picked for their stage.</p>
            </div>
            <a href="<?= base_url('shop') ?>" class="text-xs sm:text-sm font-semibold text-brand-600 hover:underline">All ages →</a>
        </div>
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <?php
            $ageBuckets = [
                ['label'=>'Tiny tots',  'sub'=>'0 – 2 yrs',  'emoji'=>'🍼', 'min'=>0,  'max'=>2,  'bg'=>'from-pink-200 to-rose-300',     'text'=>'text-pink-900'],
                ['label'=>'Preschool',  'sub'=>'3 – 5 yrs',  'emoji'=>'🧸', 'min'=>3,  'max'=>5,  'bg'=>'from-amber-200 to-orange-300',  'text'=>'text-amber-900'],
                ['label'=>'Early years','sub'=>'6 – 8 yrs',  'emoji'=>'🎨', 'min'=>6,  'max'=>8,  'bg'=>'from-emerald-200 to-lime-300',  'text'=>'text-emerald-900'],
                ['label'=>'Pre-teen',   'sub'=>'9 – 12 yrs', 'emoji'=>'🧪', 'min'=>9,  'max'=>12, 'bg'=>'from-sky-200 to-indigo-300',    'text'=>'text-sky-900'],
                ['label'=>'Teens',      'sub'=>'13+ yrs',    'emoji'=>'🚀', 'min'=>13, 'max'=>18, 'bg'=>'from-violet-200 to-fuchsia-300','text'=>'text-violet-900'],
            ];
            foreach ($ageBuckets as $b): ?>
                <a href="<?= base_url('shop?age_min=' . $b['min'] . '&age_max=' . $b['max']) ?>"
                   class="group relative block rounded-2xl bg-gradient-to-br <?= esc($b['bg']) ?> p-4 sm:p-5 shadow-sm hover:shadow-xl hover:-translate-y-1 transition overflow-hidden">
                    <div class="absolute -bottom-4 -right-3 text-6xl sm:text-7xl opacity-20 group-hover:opacity-30 group-hover:scale-110 transition duration-500 select-none">
                        <?= $b['emoji'] ?>
                    </div>
                    <div class="relative">
                        <div class="text-2xl sm:text-3xl"><?= $b['emoji'] ?></div>
                        <div class="mt-2 text-base sm:text-lg font-black <?= esc($b['text']) ?>"><?= esc($b['label']) ?></div>
                        <div class="text-xs <?= esc($b['text']) ?>/80 font-semibold uppercase tracking-wide"><?= esc($b['sub']) ?></div>
                        <div class="mt-2 inline-flex items-center gap-1 text-xs font-bold <?= esc($b['text']) ?>">
                            Browse <span class="group-hover:translate-x-1 transition">→</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ======================================================== -->
<!-- FEATURED PICKS                                           -->
<!-- ======================================================== -->
<?php if (!empty($featured)): ?>
<section class="py-10 lg:py-14 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between">
            <div>
                <span class="text-xs uppercase tracking-wider font-bold text-brand-600">⭐ Featured</span>
                <h2 class="mt-1 text-2xl lg:text-3xl font-black">Hand-picked for you</h2>
            </div>
            <a href="<?= base_url('shop') ?>" class="text-sm font-semibold text-brand-600 hover:underline hidden sm:inline">All products →</a>
        </div>
        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach (array_slice($featured, 0, 8) as $p): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- LIVE TUITIONS — recurring weekly classes                 -->
<!-- ======================================================== -->
<?php if (!empty($tuitions)): ?>
<section class="py-10 lg:py-14 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <span class="text-xs uppercase tracking-wider font-bold text-emerald-600">📅 Live · weekly</span>
            <h2 class="mt-1 text-2xl lg:text-3xl font-black">Live classes &amp; tuitions</h2>
            <p class="mt-1 text-sm text-slate-500">Same friendly teacher, same time every week. Trial class available.</p>
        </div>
        <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($tuitions as $t):
                $img = $t['hero_image'] ?? null;
                $imgSrc = $img ? (preg_match('#^https?://#', $img) ? $img : base_url($img)) : null;
                $days = json_decode($t['days_of_week'] ?? '[]', true) ?: [];
            ?>
                <div class="group flex flex-col bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl hover:-translate-y-0.5 transition">
                    <a href="<?= base_url('product/' . $t['slug']) ?>" class="block">
                        <div class="relative aspect-[4/3] bg-emerald-100 overflow-hidden">
                            <?php if ($imgSrc): ?>
                                <img src="<?= esc($imgSrc) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="">
                            <?php endif; ?>
                            <span class="absolute top-2 left-2 px-2 py-1 rounded-md bg-emerald-500 text-white text-xs font-black uppercase tracking-wide">LIVE</span>
                        </div>
                        <div class="p-4 pb-2">
                            <div class="text-[10px] uppercase tracking-wide font-bold text-emerald-600"><?= esc($t['subject']) ?></div>
                            <h3 class="mt-1 font-bold text-slate-900 line-clamp-1 group-hover:text-brand-600"><?= esc($t['name']) ?></h3>
                            <div class="mt-2 text-xs text-slate-600 space-y-0.5">
                                <div>👩‍🏫 <?= esc($t['instructor_name']) ?></div>
                                <div>📅 <?= esc(implode('/', $days)) ?>, <?= substr($t['start_time'], 0, 5) ?>–<?= substr($t['end_time'], 0, 5) ?></div>
                            </div>
                            <div class="mt-3 text-base font-black"><?= kb_money_short((int) ($t['price'] ?? 0)) ?><span class="text-xs text-slate-500 font-normal">/mo</span></div>
                        </div>
                    </a>
                    <div class="px-4 pb-4 mt-auto">
                        <a href="<?= base_url('enrol/' . (int) $t['variant_id']) ?>?intent=trial"
                           class="block w-full px-3 py-2.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-center shadow-cta hover:shadow-md transition">
                            <span class="flex items-center justify-center gap-1.5">
                                <span class="text-base leading-none">🎓</span>
                                <span class="text-xs font-black uppercase tracking-wider">Book FREE trial</span>
                                <svg class="w-3.5 h-3.5 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                            </span>
                            <span class="block text-[10px] font-medium opacity-90 mt-0.5">No card · cancel anytime</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- ONLINE COURSES                                           -->
<!-- ======================================================== -->
<?php if (!empty($courses)): ?>
<section id="courses" class="py-10 lg:py-14 bg-gradient-to-br from-indigo-50 via-white to-rose-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <span class="text-xs uppercase tracking-wider font-bold text-indigo-600">🎓 Self-paced</span>
            <h2 class="mt-1 text-2xl lg:text-3xl font-black">Online courses for curious kids</h2>
            <p class="mt-1 text-sm text-slate-500">Stream anywhere · lifetime access · certificates included.</p>
        </div>
        <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($courses as $c):
                $img = $c['hero_image'] ?? null;
                $imgSrc = $img ? (preg_match('#^https?://#', $img) ? $img : base_url($img)) : null;
            ?>
                <div class="group flex flex-col bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-2xl hover:-translate-y-1 transition">
                    <a href="<?= base_url('product/' . $c['slug']) ?>" class="block">
                        <div class="relative aspect-video bg-slate-900 overflow-hidden">
                            <?php if ($imgSrc): ?>
                                <img src="<?= esc($imgSrc) ?>" class="w-full h-full object-cover opacity-90 group-hover:opacity-100 group-hover:scale-105 transition duration-500" alt="">
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
                            <div class="absolute top-2 left-2 px-2 py-1 rounded-md bg-indigo-500 text-white text-xs font-black uppercase">COURSE</div>
                            <div class="absolute bottom-2 right-2 w-12 h-12 rounded-full bg-white/95 shadow-lg flex items-center justify-center group-hover:scale-110 transition">
                                <svg class="w-5 h-5 text-indigo-600 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        </div>
                        <div class="p-4 pb-2">
                            <h3 class="font-bold text-slate-900 line-clamp-2 group-hover:text-brand-600 min-h-[2.5em]"><?= esc($c['name']) ?></h3>
                            <div class="mt-2 text-xs text-slate-500">By <span class="font-semibold text-slate-700"><?= esc($c['instructor_name']) ?></span></div>
                            <div class="mt-2 flex gap-3 text-xs text-slate-600">
                                <span>🎬 <?= (int) $c['lessons_count'] ?> lessons</span>
                                <span>⏱️ <?= round((int) $c['total_minutes'] / 60, 1) ?>h</span>
                                <?php if (! empty($c['certificate_available'])): ?><span>🎓 Cert</span><?php endif; ?>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="text-base font-black"><?= kb_money_short((int) ($c['price'] ?? 0)) ?></span>
                                <?php if (! empty($c['compare_at_price']) && $c['compare_at_price'] > $c['price']): ?>
                                    <span class="text-xs text-slate-400 line-through"><?= kb_money_short((int) $c['compare_at_price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <div class="px-4 pb-4 mt-auto">
                        <a href="<?= base_url('enrol/' . (int) $c['variant_id']) ?>"
                           class="block w-full px-3 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-center shadow-cta hover:shadow-md transition">
                            <span class="flex items-center justify-center gap-1.5">
                                <span class="text-base leading-none">▶</span>
                                <span class="text-xs font-black uppercase tracking-wider">Enrol &amp; start watching</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                            </span>
                            <span class="block text-[10px] font-medium opacity-90 mt-0.5">Lifetime access</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- UPCOMING MEETUPS                                         -->
<!-- ======================================================== -->
<?php if (!empty($meetups)): ?>
<section class="py-10 lg:py-14 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <span class="text-xs uppercase tracking-wider font-bold text-amber-600">📍 In person</span>
            <h2 class="mt-1 text-2xl lg:text-3xl font-black">Upcoming meetups</h2>
            <p class="mt-1 text-sm text-slate-500">Come meet other Khoobie families IRL.</p>
        </div>
        <div class="mt-6 grid lg:grid-cols-3 gap-4">
            <?php foreach ($meetups as $m):
                $start = $m['starts_at'] ?? null;
            ?>
                <?php
                    $spotsLeft = (int) $m['capacity'] - (int) $m['rsvp_count'];
                    $lowSeats  = $m['capacity'] && $spotsLeft > 0 && $spotsLeft <= 5;
                ?>
                <div class="group flex flex-col bg-white rounded-2xl border border-slate-100 p-4 hover:shadow-xl hover:-translate-y-0.5 transition">
                    <a href="<?= base_url('product/' . $m['slug']) ?>" class="flex gap-4 block">
                        <?php if ($start): ?>
                            <div class="shrink-0 w-16 h-16 rounded-lg bg-amber-100 text-amber-800 flex flex-col items-center justify-center font-black">
                                <div class="text-xs uppercase"><?= date('M', strtotime($start)) ?></div>
                                <div class="text-2xl leading-none"><?= date('j', strtotime($start)) ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <?php if ((int) $m['is_free']): ?>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] uppercase tracking-wide bg-emerald-100 text-emerald-700 font-bold">Free</span>
                                <?php endif; ?>
                                <?php if ($lowSeats): ?>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] uppercase tracking-wide bg-rose-100 text-rose-700 font-bold">Only <?= $spotsLeft ?> left</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="mt-1 font-bold text-slate-900 line-clamp-2 group-hover:text-brand-600"><?= esc($m['name']) ?></h3>
                            <div class="mt-2 text-xs text-slate-600 space-y-0.5">
                                <!-- City › Locality › Area breadcrumb -->
                                <div class="flex items-center gap-1 flex-wrap text-[11px] font-semibold text-slate-700">
                                    <span>📍</span>
                                    <span><?= esc($m['city']) ?></span>
                                    <?php if (! empty($m['locality'])): ?>
                                        <span class="text-slate-300">›</span><span><?= esc($m['locality']) ?></span>
                                    <?php endif; ?>
                                    <?php if (! empty($m['area'])): ?>
                                        <span class="text-slate-300">›</span><span class="text-slate-500"><?= esc($m['area']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>📅 <?= $start ? kb_date($start, true) : 'Date TBC' ?></div>
                                <?php if ((int) $m['capacity'] && ! $lowSeats): ?>
                                    <div>👥 <?= $spotsLeft ?> spots left of <?= (int) $m['capacity'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <div class="mt-3">
                        <a href="<?= base_url('enrol/' . (int) $m['variant_id']) ?>"
                           class="block w-full px-3 py-2.5 rounded-lg bg-rose-500 hover:bg-rose-600 text-white text-center shadow-cta hover:shadow-md transition">
                            <span class="flex items-center justify-center gap-1.5">
                                <span class="text-base leading-none">📍</span>
                                <span class="text-xs font-black uppercase tracking-wider"><?= (int) $m['is_free'] ? 'RSVP Free' : 'Reserve seat' ?></span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                            </span>
                            <span class="block text-[10px] font-medium opacity-90 mt-0.5">
                                <?php if ($lowSeats): ?>Filling fast — book now<?php elseif ((int) $m['is_free']): ?>No payment needed<?php else: ?>Venue map on WhatsApp<?php endif; ?>
                            </span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- MEMBERSHIP BANNER                                        -->
<!-- ======================================================== -->
<?php if (!empty($memberships)): $top = $memberships[0]; $perks = json_decode($top['perks'] ?? '[]', true) ?: []; ?>
<section class="py-10 lg:py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-brand-700 to-rose-700 text-white p-8 lg:p-12">
            <div class="absolute top-0 right-0 w-72 h-72 rounded-full bg-amber-400/20 blur-3xl"></div>
            <div class="absolute -bottom-12 -left-12 w-64 h-64 rounded-full bg-brand-400/30 blur-3xl"></div>

            <div class="relative grid lg:grid-cols-[2fr_1fr] gap-8 items-center">
                <div>
                    <span class="inline-block px-3 py-1 rounded-full bg-white/15 backdrop-blur text-amber-200 text-xs font-bold tracking-wide uppercase">★ Khoobie <?= esc($top['tier_name']) ?></span>
                    <h2 class="mt-3 text-3xl lg:text-4xl font-black"><?= esc($top['name']) ?></h2>
                    <p class="mt-2 text-slate-200 max-w-lg"><?= esc($top['description']) ?></p>
                    <ul class="mt-5 grid sm:grid-cols-2 gap-2 text-sm">
                        <?php foreach (array_slice($perks, 0, 6) as $perk): ?>
                            <li class="flex gap-2"><span class="text-amber-300 mt-0.5">✓</span><span><?= esc($perk) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="bg-white text-slate-900 rounded-2xl p-6 shadow-2xl">
                    <div class="text-xs uppercase tracking-wide text-slate-500 font-bold">Starts at</div>
                    <div class="mt-1"><span class="text-4xl font-black"><?= kb_money_short((int) $top['monthly_price']) ?></span><span class="text-sm text-slate-500"> / month</span></div>
                    <a href="<?= base_url('product/' . $top['slug']) ?>" class="mt-4 block text-center btn-primary">Join Insider →</a>
                    <div class="mt-2 text-[11px] text-slate-500 text-center">Cancel anytime · billed monthly</div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- BEST SELLERS                                             -->
<!-- ======================================================== -->
<?php if (!empty($bestSellers)): ?>
<section class="py-10 lg:py-14 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between">
            <div>
                <span class="text-xs uppercase tracking-wider font-bold text-rose-600">🔥 Best sellers</span>
                <h2 class="mt-1 text-2xl lg:text-3xl font-black">Parents keep coming back for these</h2>
            </div>
            <a href="<?= base_url('shop') ?>?sort=bestselling" class="text-sm font-semibold text-brand-600 hover:underline hidden sm:inline">View all →</a>
        </div>
        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach (array_slice($bestSellers, 0, 8) as $p): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- 1-on-1 SERVICES                                          -->
<!-- ======================================================== -->
<?php if (!empty($services)): ?>
<section class="py-10 lg:py-14 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <span class="text-xs uppercase tracking-wider font-bold text-sky-600">🤝 1-on-1</span>
            <h2 class="mt-1 text-2xl lg:text-3xl font-black">Book a Khoobie expert</h2>
            <p class="mt-1 text-sm text-slate-500">Tutoring, parent consultations, even birthday party planning.</p>
        </div>
        <div class="mt-6 grid lg:grid-cols-3 gap-4">
            <?php foreach ($services as $s):
                $kindIcons = ['tutoring'=>'📚','consultation'=>'💬','party_planning'=>'🎉','toy_rental'=>'🧸','custom'=>'🔧'];
                $icon = $kindIcons[$s['service_kind']] ?? '🔧';
            ?>
                <div class="group flex flex-col bg-gradient-to-br from-sky-50 to-white border border-sky-100 rounded-2xl p-5 hover:shadow-xl hover:-translate-y-0.5 transition">
                    <a href="<?= base_url('product/' . $s['slug']) ?>" class="block">
                        <div class="flex items-start justify-between">
                            <div class="text-4xl"><?= $icon ?></div>
                            <span class="text-[10px] uppercase tracking-wide bg-sky-100 text-sky-700 px-2 py-0.5 rounded font-bold"><?= esc(str_replace('_', ' ', $s['service_kind'])) ?></span>
                        </div>
                        <h3 class="mt-3 font-bold text-slate-900 line-clamp-2 group-hover:text-brand-600"><?= esc($s['name']) ?></h3>
                        <?php if (! empty($s['provider_name'])): ?>
                            <div class="mt-1 text-xs text-slate-500">With <?= esc($s['provider_name']) ?></div>
                        <?php endif; ?>
                        <div class="mt-3 text-xs text-slate-600">⏱️ <?= (int) $s['duration_minutes'] ?> min · <?= ucfirst(str_replace('_', ' ', $s['modality'])) ?></div>
                        <div class="mt-3 text-base font-black"><?= kb_money_short((int) ($s['price'] ?? 0)) ?></div>
                    </a>
                    <div class="mt-4">
                        <a href="<?= base_url('enrol/' . (int) $s['variant_id']) ?>"
                           class="block w-full px-3 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-center shadow-cta hover:shadow-md transition">
                            <span class="flex items-center justify-center gap-1.5">
                                <span class="text-base leading-none">🤝</span>
                                <span class="text-xs font-black uppercase tracking-wider">Book a slot</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                            </span>
                            <span class="block text-[10px] font-medium opacity-90 mt-0.5">Pick your own time</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- AFFILIATE / CURATED PICKS                                -->
<!-- ======================================================== -->
<?php if (!empty($affiliates)): ?>
<section class="py-10 lg:py-14 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <span class="text-xs uppercase tracking-wider font-bold text-orange-600">🛍️ Curated</span>
            <h2 class="mt-1 text-2xl lg:text-3xl font-black">From Amazon &amp; Flipkart, hand-picked by us</h2>
            <p class="mt-1 text-sm text-slate-500">When we love something we don't make, we'll just send you there. No extra cost to you.</p>
        </div>
        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($affiliates as $a):
                $img = $a['hero_image'] ?? null;
                $imgSrc = $img ? (preg_match('#^https?://#', $img) ? $img : base_url($img)) : null;
            ?>
                <?php
                    // Per-marketplace button tone — Amazon orange, Flipkart blue, etc.
                    $partnerKey = strtolower($a['partner_name'] ?? '');
                    $btnTone = match (true) {
                        str_contains($partnerKey, 'amazon')   => 'bg-[#FF9900] hover:bg-[#E68A00]',
                        str_contains($partnerKey, 'flipkart') => 'bg-[#2874F0] hover:bg-[#1F5FBF]',
                        str_contains($partnerKey, 'meesho')   => 'bg-[#9F2089] hover:bg-[#7E1A6E]',
                        str_contains($partnerKey, 'myntra')   => 'bg-[#FF3F6C] hover:bg-[#E0355E]',
                        str_contains($partnerKey, 'firstcry') => 'bg-[#C50056] hover:bg-[#9F0044]',
                        default                                => 'bg-orange-500 hover:bg-orange-600',
                    };
                ?>
                <div class="group flex flex-col bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl hover:-translate-y-0.5 transition">
                    <a href="<?= base_url('product/' . $a['slug']) ?>" class="block">
                        <div class="relative aspect-square bg-slate-100 overflow-hidden">
                            <?php if ($imgSrc): ?>
                                <img src="<?= esc($imgSrc) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="">
                            <?php endif; ?>
                            <span class="absolute top-2 left-2 px-2 py-1 rounded-md bg-orange-500 text-white text-xs font-black uppercase">
                                via <?= esc($a['partner_name']) ?>
                            </span>
                        </div>
                        <div class="p-4 pb-2">
                            <h3 class="text-sm font-bold text-slate-900 line-clamp-2 group-hover:text-brand-600 min-h-[2.5em]"><?= esc($a['name']) ?></h3>
                            <div class="mt-2 text-base font-black"><?= kb_money_short((int) ($a['price'] ?? 0)) ?></div>
                        </div>
                    </a>
                    <div class="px-4 pb-4 mt-auto">
                        <a href="<?= base_url('go/' . $a['slug']) ?>" target="_blank" rel="noopener nofollow sponsored"
                           class="block w-full px-3 py-2.5 rounded-lg text-white text-center shadow-cta hover:shadow-md transition <?= $btnTone ?>">
                            <span class="flex items-center justify-center gap-1.5">
                                <span class="text-xs font-black uppercase tracking-wider">Buy on <?= esc($a['partner_name']) ?></span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M7 17 17 7M7 7h10v10"/></svg>
                            </span>
                            <span class="block text-[10px] font-medium opacity-90 mt-0.5">Khoobie-picked · checkout there</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ======================================================== -->
<!-- TESTIMONIALS                                             -->
<!-- ======================================================== -->
<section class="py-12 lg:py-16 bg-gradient-to-br from-amber-50 to-rose-50">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl lg:text-3xl font-black">What parents are saying</h2>
        <div class="mt-3 text-amber-500 text-lg">
            ★★★★★ <span class="text-slate-700 font-semibold ml-1"><?= number_format($stats['avg_rating'], 1) ?>/5</span>
            <span class="text-slate-500 text-sm ml-1">from <?= number_format($stats['review_count']) ?> reviews</span>
        </div>
        <?php if (!empty($reviews)): ?>
            <div class="mt-8 grid lg:grid-cols-3 gap-5">
                <?php foreach ($reviews as $r): ?>
                    <figure class="bg-white p-5 rounded-2xl shadow-sm text-left">
                        <div class="text-amber-500"><?= str_repeat('★', (int) $r['rating']) ?><span class="text-slate-200"><?= str_repeat('★', 5 - (int) $r['rating']) ?></span></div>
                        <?php if ($r['title']): ?><div class="mt-2 font-bold"><?= esc($r['title']) ?></div><?php endif; ?>
                        <blockquote class="mt-1 text-sm text-slate-700 line-clamp-5"><?= esc($r['body']) ?></blockquote>
                        <figcaption class="mt-4 flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-sm"><?= esc($r['reviewer_name']) ?></div>
                                <a href="<?= base_url('product/' . $r['product_slug']) ?>" class="text-xs text-slate-500 hover:text-brand-600">on <?= esc($r['product_name']) ?></a>
                            </div>
                            <?php if ($r['is_verified_buyer']): ?>
                                <span class="text-[10px] uppercase tracking-wide bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Verified</span>
                            <?php endif; ?>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="mt-8 grid lg:grid-cols-3 gap-5">
                <?php $sample = [
                    ['name' => 'Priya M., Bangalore',  'product' => 'DIY Paint Kit Krishna', 'text' => 'My 7-year-old was glued to this for 3 hours. The wooden cutouts were so smooth and the colours were genuinely vibrant. Worth every rupee.'],
                    ['name' => 'Anjali S., Mumbai',    'product' => 'Mandala Art Course',     'text' => 'Riya\'s teaching style is so warm. My daughter has been waiting for the next lesson every single day. Best ₹1,499 we\'ve spent.'],
                    ['name' => 'Vikram P., Delhi NCR', 'product' => 'Garden Grow Kit',        'text' => 'Honestly didn\'t expect the seeds to actually grow this well. Our balcony has marigolds now! And my son is obsessed with watering them.'],
                ];
                foreach ($sample as $r): ?>
                    <figure class="bg-white p-5 rounded-2xl shadow-sm text-left">
                        <div class="text-amber-500">★★★★★</div>
                        <blockquote class="mt-2 text-sm text-slate-700"><?= esc($r['text']) ?></blockquote>
                        <figcaption class="mt-4">
                            <div class="font-semibold text-sm"><?= esc($r['name']) ?></div>
                            <div class="text-xs text-slate-500">on <?= esc($r['product']) ?></div>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ======================================================== -->
<!-- BY THE NUMBERS                                           -->
<!-- ======================================================== -->
<section class="py-14 lg:py-20 bg-slate-900 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl lg:text-3xl font-black">Khoobie, by the numbers</h2>
        <div class="mt-10 grid grid-cols-2 lg:grid-cols-4 gap-8">
            <div>
                <div class="text-5xl font-black text-brand-400"><?= number_format($stats['products']) ?>+</div>
                <div class="mt-2 text-sm uppercase tracking-wide text-slate-400">Active products</div>
            </div>
            <div>
                <div class="text-5xl font-black text-amber-400"><?= $stats['courses'] + $stats['tuitions'] ?>+</div>
                <div class="mt-2 text-sm uppercase tracking-wide text-slate-400">Classes &amp; courses</div>
            </div>
            <div>
                <div class="text-5xl font-black text-emerald-400"><?= number_format($stats['customers']) ?>+</div>
                <div class="mt-2 text-sm uppercase tracking-wide text-slate-400">Happy parents</div>
            </div>
            <div>
                <div class="text-5xl font-black text-rose-400"><?= number_format($stats['review_count']) ?>+</div>
                <div class="mt-2 text-sm uppercase tracking-wide text-slate-400">Glowing reviews</div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================== -->
<!-- INSTAGRAM TEASER                                         -->
<!-- ======================================================== -->
<section class="py-12 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-6">
            <div>
                <span class="text-xs uppercase tracking-wider font-bold text-pink-600">📸 @khoobiecreations</span>
                <h2 class="mt-1 text-2xl lg:text-3xl font-black">From the Khoobie family</h2>
            </div>
            <a href="https://instagram.com/khoobiecreations" target="_blank" class="text-sm font-semibold text-brand-600 hover:underline">Follow on Instagram →</a>
        </div>
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-2">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <a href="https://instagram.com/khoobiecreations" target="_blank" class="block aspect-square overflow-hidden rounded-lg group">
                    <img src="https://picsum.photos/seed/khoobie-insta-<?= $i ?>/400/400" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" alt="Khoobie Instagram">
                </a>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ======================================================== -->
<!-- RAFFLE / NEWSLETTER                                      -->
<!-- ======================================================== -->
<section id="raffle" class="py-14 bg-gradient-to-br from-brand-500 to-rose-600 text-white">
    <div class="mx-auto max-w-3xl px-4 text-center">
        <div class="text-4xl">🎁</div>
        <h2 class="mt-3 text-3xl lg:text-4xl font-black">Win a ₹3,000 Khoobie Goodie Box</h2>
        <p class="mt-2 text-rose-50 max-w-lg mx-auto">Drop your details below. One lucky parent every week wins a curated box of screen-free fun + first dibs on launches and 10% off your next order.</p>
        <form method="post" action="<?= base_url('lead/raffle') ?>" class="mt-6 grid sm:grid-cols-2 gap-3 max-w-xl mx-auto text-left">
            <?= csrf_field() ?>
            <?= $this->include('partials/_honeypot') ?>
            <input name="name" placeholder="Your name" required class="px-4 py-3 rounded-lg text-slate-900 placeholder-slate-400">
            <input name="phone" type="tel" placeholder="Phone (WhatsApp)" required class="px-4 py-3 rounded-lg text-slate-900 placeholder-slate-400">
            <input name="email" type="email" placeholder="Email" required class="sm:col-span-2 px-4 py-3 rounded-lg text-slate-900 placeholder-slate-400">
            <button class="sm:col-span-2 px-6 py-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold">Enter the raffle &rarr;</button>
        </form>
        <p class="mt-3 text-xs text-rose-100">By entering, you agree to receive offers from Khoobie Creations. Unsubscribe anytime.</p>
    </div>
</section>

<!-- ======================================================== -->
<!-- TRUST BAR                                                -->
<!-- ======================================================== -->
<section class="bg-white border-t border-slate-100">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
        <div><div class="text-3xl">🚚</div><div class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500">Free shipping ₹999+</div></div>
        <div><div class="text-3xl">💰</div><div class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500">COD across India</div></div>
        <div><div class="text-3xl">↩️</div><div class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500">7-day returns</div></div>
        <div><div class="text-3xl">💬</div><div class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500">WhatsApp support</div></div>
    </div>
</section>

<?= $this->endSection() ?>
