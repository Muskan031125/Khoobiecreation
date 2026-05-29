<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php
$default = 'razorpay';
foreach (['free_trial','partial_venue','razorpay'] as $cand) {
    if (! empty($methods[$cand]['available'])) { $default = $cand; break; }
}
// Honour ?intent=trial — when a PDP "Book trial" button funnels here, pre-select
// the free-trial radio if the type actually supports it.
$intent = $_GET['intent'] ?? '';
if ($intent === 'trial' && ! empty($methods['free_trial']['available'])) {
    $default = 'free_trial';
} elseif ($intent === 'reserve' && ! empty($methods['partial_venue']['available'])) {
    $default = 'partial_venue';
}
$hero = $item['hero_image'] ?? null;
if ($hero && ! preg_match('#^https?://#', $hero)) $hero = base_url($hero);
?>

<section class="py-6 sm:py-10 lg:py-14 bg-slate-50 min-h-screen">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 lg:px-6">

        <nav class="text-[11px] sm:text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-2">
            <a href="<?= base_url('product/' . $item['p_slug']) ?>" class="hover:underline">← Back</a>
            <span class="mx-1">·</span>
            <span class="text-slate-900 font-semibold">Express enrol</span>
        </nav>

        <div class="flex items-end justify-between gap-3 flex-wrap mb-4">
            <div>
                <span class="eyebrow text-violet-600">⚡ One enrolment · one payment</span>
                <h1 class="h-display text-2xl sm:text-3xl lg:text-4xl mt-1 text-slate-900">Complete your booking</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Classes &amp; events check out one at a time so each gets the right access flow.
                </p>
            </div>
        </div>

        <?php if (session('errors')): ?>
            <ul class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm list-disc ml-5">
                <?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= base_url('enrol/' . $item['id'] . '/pay') ?>" class="grid lg:grid-cols-[1fr_400px] gap-4 lg:gap-6" x-data="{ payment: '<?= esc($default, 'attr') ?>' }">
            <?= csrf_field() ?>

            <div class="space-y-4">
                <!-- Contact -->
                <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-soft">
                    <h2 class="font-display text-base sm:text-lg font-black flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-500 text-white text-xs">1</span>
                        Your contact info
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">We'll send the access details + reminders here.</p>
                    <div class="mt-3 grid sm:grid-cols-2 gap-3">
                        <input name="name"  required placeholder="Parent's name *" value="<?= esc($prefill['name'] ?? '') ?>" class="px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none text-sm">
                        <input name="phone" required placeholder="Phone (WhatsApp) *" type="tel" maxlength="10" value="<?= esc($prefill['phone'] ?? '') ?>" class="px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none text-sm">
                        <input name="email" required placeholder="Email *" type="email" value="<?= esc($prefill['email'] ?? '') ?>" class="sm:col-span-2 px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none text-sm">
                    </div>
                    <?php if (in_array($item['p_type'], ['tuition','course','meetup'], true)): ?>
                        <div class="mt-3 grid sm:grid-cols-2 gap-3">
                            <input name="child_name" placeholder="Child's name (optional)" class="px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none text-sm">
                            <input name="child_age" placeholder="Child's age (optional)" type="number" min="2" max="18" class="px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none text-sm">
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Payment -->
                <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-soft">
                    <h2 class="font-display text-base sm:text-lg font-black flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-500 text-white text-xs">2</span>
                        How would you like to pay?
                    </h2>
                    <div class="mt-3 space-y-2">
                        <?php foreach (['free_trial','partial_venue','razorpay','phonepe'] as $mkey):
                            if (empty($methods[$mkey])) continue;
                            $m = $methods[$mkey];
                            if (! $m['available']) continue;
                        ?>
                            <label class="block rounded-xl border-2 cursor-pointer transition"
                                   :class="payment === '<?= $mkey ?>' ? 'border-brand-500 bg-brand-50/50' : 'border-slate-200 hover:border-slate-300'">
                                <div class="flex items-start gap-3 p-3 sm:p-4">
                                    <input type="radio" name="payment_method" value="<?= $mkey ?>" x-model="payment" class="mt-1 accent-brand-500">
                                    <div class="text-2xl shrink-0 leading-none"><?= $m['icon'] ?></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-sm text-slate-900"><?= esc($m['label']) ?></div>
                                        <div class="text-xs text-slate-600 mt-0.5"><?= esc($m['sub']) ?></div>
                                        <?php if ($mkey === 'free_trial'): ?>
                                            <div class="mt-2 text-[11px] text-emerald-700 bg-emerald-50 rounded px-2 py-1">
                                                ✓ No card needed · we'll only charge after the trial if you continue
                                            </div>
                                        <?php elseif ($mkey === 'partial_venue'): ?>
                                            <div class="mt-2 text-[11px] text-amber-800 bg-amber-50 rounded px-2 py-1">
                                                💡 Pay <strong>₹<?= number_format(round($m['advance']/100)) ?></strong> now · carry <strong>₹<?= number_format(round($m['balance']/100)) ?></strong> in cash / UPI · refundable up to 48h before
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- Summary aside -->
            <aside class="bg-white rounded-2xl p-4 sm:p-5 h-fit lg:sticky lg:top-24 shadow-soft space-y-3">
                <div class="flex gap-3 items-start">
                    <?php if ($hero): ?>
                        <img src="<?= esc($hero) ?>" alt="" class="w-20 h-20 rounded-lg object-cover ring-1 ring-slate-200 shrink-0">
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <span class="eyebrow text-violet-600"><?= esc($meta['type_label']) ?></span>
                        <h3 class="font-display font-black text-slate-900 leading-tight mt-0.5 line-clamp-2"><?= esc($item['p_name']) ?></h3>
                    </div>
                </div>

                <!-- Type-specific facts -->
                <div class="space-y-1.5 text-xs text-slate-700 pt-2 border-t border-slate-100">
                    <?php if (! empty($meta['instructor'])): ?>
                        <div class="flex justify-between"><span>👩‍🏫 Instructor</span><span class="font-semibold"><?= esc($meta['instructor']) ?></span></div>
                    <?php endif; ?>
                    <?php if (! empty($meta['provider'])): ?>
                        <div class="flex justify-between"><span>👤 Provider</span><span class="font-semibold"><?= esc($meta['provider']) ?></span></div>
                    <?php endif; ?>
                    <?php if (! empty($meta['schedule'])): ?>
                        <div class="flex justify-between"><span>📅 Schedule</span><span class="font-semibold text-right"><?= esc($meta['schedule']) ?></span></div>
                    <?php endif; ?>
                    <?php if (! empty($meta['lessons'])): ?>
                        <div class="flex justify-between"><span>🎬 Lessons</span><span class="font-semibold"><?= (int) $meta['lessons'] ?> · <?= esc($meta['hours']) ?>h</span></div>
                    <?php endif; ?>
                    <?php if (! empty($meta['starts_at'])): ?>
                        <div class="flex justify-between"><span>📅 When</span><span class="font-semibold text-right"><?= kb_date($meta['starts_at'], true, 'short') ?></span></div>
                    <?php endif; ?>
                    <?php if (! empty($meta['city'])): ?>
                        <div class="flex justify-between"><span>📍 Where</span><span class="font-semibold text-right"><?= esc(($meta['locality'] ? $meta['locality'] . ', ' : '') . $meta['city']) ?></span></div>
                    <?php endif; ?>
                    <?php if (! empty($meta['capacity_left'])): ?>
                        <div class="flex justify-between"><span>👥 Capacity</span><span class="font-semibold text-amber-700"><?= (int) $meta['capacity_left'] ?> spots left</span></div>
                    <?php endif; ?>
                    <?php if (! empty($meta['duration'])): ?>
                        <div class="flex justify-between"><span>⏱ Duration</span><span class="font-semibold"><?= (int) $meta['duration'] ?> min</span></div>
                    <?php endif; ?>
                    <?php if (! empty($meta['certificate'])): ?>
                        <div class="flex justify-between"><span>🎓 Certificate</span><span class="font-semibold text-emerald-700">Included</span></div>
                    <?php endif; ?>
                </div>

                <!-- Price -->
                <div class="pt-3 border-t border-slate-200">
                    <div class="flex justify-between text-lg font-black">
                        <span>Price</span>
                        <span class="tabular-nums">₹<?= number_format(round((int) $item['price'] / 100)) ?></span>
                    </div>

                    <!-- Adaptive "pay now / balance later" hint -->
                    <div class="mt-2 px-3 py-2.5 rounded-lg bg-slate-50 text-xs">
                        <div x-show="payment === 'razorpay' || payment === 'phonepe'" class="flex items-center justify-between font-bold">
                            <span>Pay now</span>
                            <span class="text-brand-600">₹<?= number_format(round((int) $item['price'] / 100)) ?></span>
                        </div>
                        <?php if (! empty($methods['partial_venue']['available'])): ?>
                            <div x-show="payment === 'partial_venue'" x-cloak class="space-y-1">
                                <div class="flex justify-between"><span>Pay now</span><span class="font-bold text-brand-600">₹<?= number_format(round($methods['partial_venue']['advance']/100)) ?></span></div>
                                <div class="flex justify-between text-slate-500"><span>At venue / class</span><span>₹<?= number_format(round($methods['partial_venue']['balance']/100)) ?></span></div>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($methods['free_trial']['available'])): ?>
                            <div x-show="payment === 'free_trial'" x-cloak class="space-y-1">
                                <div class="flex justify-between"><span>Pay now</span><span class="font-bold text-emerald-700">₹0</span></div>
                                <div class="flex justify-between text-slate-500"><span>After trial (if continuing)</span><span>₹<?= number_format(round((int) $item['price']/100)) ?></span></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="w-full h-12 rounded-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-sm uppercase tracking-wider shadow-cta hover:shadow-cta-lg hover:-translate-y-0.5 transition">
                    <span x-show="payment === 'razorpay' || payment === 'phonepe'">Pay & enrol →</span>
                    <span x-show="payment === 'partial_venue'" x-cloak>Reserve seat →</span>
                    <span x-show="payment === 'free_trial'"    x-cloak>Start free trial →</span>
                </button>
                <p class="text-[10px] text-center text-slate-500">By enrolling you agree to Khoobie's terms · cancel anytime before the first session.</p>

                <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100 text-[10px] text-slate-600">
                    <div class="text-center"><div class="text-lg">🔒</div>Secure</div>
                    <div class="text-center"><div class="text-lg">↩️</div>Refundable</div>
                    <div class="text-center"><div class="text-lg">📞</div>Support 9–9</div>
                </div>
            </aside>
        </form>
    </div>
</section>

<?= $this->endSection() ?>
