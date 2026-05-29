<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php $submitted = (bool) ($_GET['submitted'] ?? false); ?>

<!-- ===== Hero ===== -->
<section class="relative overflow-hidden bg-gradient-to-br from-emerald-50 via-amber-50 to-rose-50 py-12 sm:py-16 lg:py-24">
    <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute -bottom-20 -left-20 w-80 h-80 rounded-full bg-rose-200/40 blur-3xl"></div>
    <div class="relative mx-auto max-w-5xl px-3 sm:px-4 lg:px-6 grid lg:grid-cols-[1.2fr_1fr] gap-10 items-center">
        <div>
            <span class="eyebrow text-emerald-700">🤝 Sell with Khoobie</span>
            <h1 class="h-display text-3xl sm:text-5xl lg:text-6xl mt-2 text-slate-900">Your craft. Our customers. Their kids.</h1>
            <p class="mt-4 text-base sm:text-lg text-slate-700 max-w-xl">
                Whether you're a kid-product brand, an instructor running classes, a pottery studio, or a creator selling printables — Khoobie brings you parents who already trust us.
            </p>
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="#apply" class="btn-primary">Apply to join →</a>
                <a href="#how" class="btn-ghost">How it works</a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white rounded-2xl p-4 shadow-soft">
                <div class="text-3xl font-display font-black text-emerald-600">2,300+</div>
                <div class="text-xs text-slate-500 font-bold mt-1">Active Khoobie parents</div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-soft">
                <div class="text-3xl font-display font-black text-violet-600">15%</div>
                <div class="text-xs text-slate-500 font-bold mt-1">Standard commission</div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-soft">
                <div class="text-3xl font-display font-black text-rose-600">11</div>
                <div class="text-xs text-slate-500 font-bold mt-1">Metros covered</div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-soft">
                <div class="text-3xl font-display font-black text-amber-600">Weekly</div>
                <div class="text-xs text-slate-500 font-bold mt-1">Friday payouts</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Who fits ===== -->
<section class="py-10 sm:py-14 bg-white">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 lg:px-6">
        <h2 class="h-display text-2xl sm:text-3xl text-center">Who fits on Khoobie</h2>
        <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <?php foreach ([
                ['🎁','Kid-product brands','Ship your existing catalog to a pre-built audience of Indian parents.'],
                ['🎓','Class instructors','List your weekly tuition, recorded course, or one-off workshop — set your own price.'],
                ['🏛️','Studios & academies','Fill your weekend slots with hyperlocal visibility (city → locality → area).'],
                ['🎨','Creators & makers','Sell digital printables, ebooks, and physical creations directly.'],
            ] as $card): ?>
                <div class="bg-slate-50 rounded-2xl p-5">
                    <div class="text-4xl"><?= $card[0] ?></div>
                    <h3 class="mt-3 font-display font-black text-lg"><?= esc($card[1]) ?></h3>
                    <p class="mt-1 text-sm text-slate-600"><?= esc($card[2]) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== How it works ===== -->
<section id="how" class="py-10 sm:py-14 bg-slate-50">
    <div class="mx-auto max-w-3xl px-3 sm:px-4 lg:px-6">
        <h2 class="h-display text-2xl sm:text-3xl text-center">How it works</h2>
        <ol class="mt-6 space-y-4">
            <?php foreach ([
                ['1','Apply',          'Fill the form below — takes 2 minutes.'],
                ['2','Verification',   'Our team reviews your application within 2 business days. Quick KYC + a small product/class sample.'],
                ['3','Onboarding call','30 minutes with your Khoobie partner manager to set up listings, pricing, and fulfillment preferences.'],
                ['4','Go live',        'Your products / classes appear on Khoobie. You handle the experience; we handle marketing, payments, and customer service.'],
                ['5','Get paid',       'Weekly Friday payouts via UPI / NEFT. See every order, payment, and statement in the Partner Portal.'],
            ] as $step): ?>
                <li class="flex items-start gap-4 bg-white rounded-2xl p-5 shadow-soft">
                    <span class="shrink-0 w-10 h-10 rounded-full bg-brand-500 text-white font-display font-black text-lg inline-flex items-center justify-center"><?= $step[0] ?></span>
                    <div>
                        <h3 class="font-display font-black"><?= esc($step[1]) ?></h3>
                        <p class="mt-1 text-sm text-slate-600"><?= esc($step[2]) ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<!-- ===== Apply ===== -->
<section id="apply" class="py-10 sm:py-14 bg-gradient-to-br from-brand-50 to-amber-50">
    <div class="mx-auto max-w-2xl px-3 sm:px-4 lg:px-6">

        <?php if ($submitted): ?>
            <div class="bg-white rounded-2xl p-8 sm:p-10 text-center shadow-soft">
                <div class="text-5xl">✓</div>
                <h2 class="h-display text-2xl mt-3 text-emerald-700">Application received!</h2>
                <p class="mt-2 text-slate-600">We'll reach out to <strong>you</strong> within 2 business days. Meanwhile, browse our existing partners on the homepage to get a feel for the marketplace.</p>
                <a href="<?= base_url('shop') ?>" class="mt-5 inline-block btn-primary">Back to shop</a>
            </div>
        <?php else: ?>
            <h2 class="h-display text-3xl sm:text-4xl text-center">Apply to become a Khoobie partner</h2>
            <p class="mt-2 text-center text-slate-600">Free to apply · no listing fees · 15% commission · weekly payouts</p>

            <form method="post" action="<?= base_url('sell-with-khoobie') ?>" class="mt-6 bg-white rounded-2xl shadow-soft-lg p-6 sm:p-8 space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">What best describes you? *</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <?php foreach (['brand'=>'🎁 Brand','instructor'=>'🎓 Instructor','studio'=>'🏛️ Studio','creator'=>'🎨 Creator'] as $k => $lbl): ?>
                            <label class="flex items-center gap-2 px-3 py-2 rounded-lg border-2 border-slate-200 hover:border-brand-400 cursor-pointer has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="radio" name="kind" value="<?= $k ?>" required class="accent-brand-500">
                                <span class="text-sm font-semibold"><?= $lbl ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Company / Brand name *</label>
                        <input name="company_name" required class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Your name *</label>
                        <input name="contact_name" required class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Email *</label>
                        <input name="email" type="email" required class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Phone *</label>
                        <input name="phone" type="tel" maxlength="10" pattern="[6-9][0-9]{9}" required class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Primary city</label>
                        <input name="city" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Fulfillment</label>
                        <select name="fulfillment_type" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none">
                            <option value="drop_ship">I ship directly</option>
                            <option value="warehouse_deliver">Khoobie ships from its warehouse</option>
                            <option value="both">Either / case-by-case</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Tell us about your products / classes</label>
                    <textarea name="message" rows="3" placeholder="What do you make / teach? How many SKUs / classes do you run? Anything else we should know."
                              class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full h-12 rounded-full bg-brand-500 hover:bg-brand-600 text-white font-bold uppercase tracking-wider shadow-cta hover:shadow-cta-lg transition">
                    Apply to join Khoobie
                </button>
                <p class="text-[11px] text-center text-slate-500">By applying you agree to our partner terms. We'll never share your details.</p>
            </form>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
