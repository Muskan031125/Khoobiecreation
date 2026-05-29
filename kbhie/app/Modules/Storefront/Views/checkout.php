<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php
$methods       = $eligibility['methods'] ?? [];
$composition   = $eligibility['composition'] ?? [];
$flags         = $eligibility['flags'] ?? [];
$default       = 'razorpay';
foreach (['cod','partial_venue','razorpay'] as $cand) {
    if (! empty($methods[$cand]['available'])) { $default = $cand; break; }
}
?>

<section class="py-6 sm:py-10 lg:py-14 bg-slate-50 min-h-screen">
    <div class="mx-auto max-w-6xl px-3 sm:px-4 lg:px-6">

        <nav class="text-[11px] sm:text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-2">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <a href="<?= base_url('cart') ?>" class="hover:underline">Cart</a> <span>&raquo;</span>
            <span class="text-slate-900 font-semibold">Checkout</span>
        </nav>

        <div class="flex items-end justify-between gap-3 flex-wrap mb-4">
            <div>
                <span class="eyebrow text-brand-600">🔒 Secure checkout</span>
                <h1 class="h-display text-2xl sm:text-3xl lg:text-4xl mt-1 text-slate-900">Almost there</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Pre-filled what we know · pay only for what's in your cart.</p>
            </div>
        </div>

        <?php if (session('errors')): ?>
            <ul class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm list-disc ml-5">
                <?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- What's in your cart — composition pills so user knows why options below differ -->
        <?php if (array_sum($composition) > 0): ?>
            <div class="mb-4 flex flex-wrap gap-1.5 text-[11px] font-bold">
                <?php if (! empty($composition['physical'])): ?>
                    <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-700">📦 <?= $composition['physical'] ?> physical kit<?= $composition['physical'] === 1 ? '' : 's' ?></span>
                <?php endif; ?>
                <?php if (! empty($composition['digital'])): ?>
                    <span class="px-2 py-1 rounded-full bg-violet-100 text-violet-700">💾 <?= $composition['digital'] ?> digital / course</span>
                <?php endif; ?>
                <?php if (! empty($composition['offline'])): ?>
                    <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-700">📍 <?= $composition['offline'] ?> in-person booking</span>
                <?php endif; ?>
                <?php if (! empty($composition['recurring'])): ?>
                    <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">🔁 <?= $composition['recurring'] ?> recurring</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('checkout/place') ?>" class="grid lg:grid-cols-[1fr_380px] gap-4 lg:gap-6"
              x-data="{ payment: '<?= esc($default, 'attr') ?>' }">
            <?= csrf_field() ?>

            <div class="space-y-4">
                <!-- ===== Contact ===== -->
                <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-soft">
                    <h2 class="font-display text-base sm:text-lg font-black flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-500 text-white text-xs">1</span>
                        Contact
                    </h2>
                    <div class="mt-3 grid sm:grid-cols-2 gap-3">
                        <input name="name"  required placeholder="Full name *" value="<?= esc(old('name',  $prefill['name']  ?? '')) ?>" class="px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
                        <input name="phone" required placeholder="Phone (WhatsApp) *" type="tel" maxlength="10" pattern="[6-9][0-9]{9}" value="<?= esc(old('phone', $prefill['phone'] ?? '')) ?>" class="px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
                        <input name="email" required placeholder="Email *" type="email" value="<?= esc(old('email', $prefill['email'] ?? '')) ?>" class="sm:col-span-2 px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
                    </div>
                </section>

                <!-- ===== Shipping — only when cart has physical goods ===== -->
                <?php if (! empty($needsShipping)): ?>
                    <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-soft">
                        <h2 class="font-display text-base sm:text-lg font-black flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-500 text-white text-xs">2</span>
                            Shipping address
                        </h2>
                        <div class="mt-3 grid sm:grid-cols-2 gap-3">
                            <input name="line1" required placeholder="House / Flat / Building *" value="<?= esc(old('line1', $prefill['line1'] ?? '')) ?>" class="sm:col-span-2 px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
                            <input name="line2" placeholder="Area / Landmark (optional)" value="<?= esc(old('line2', $prefill['line2'] ?? '')) ?>" class="sm:col-span-2 px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
                            <input name="city"    required placeholder="City *"    value="<?= esc(old('city',    $prefill['city']    ?? '')) ?>" class="px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
                            <input name="state"   required placeholder="State *"   value="<?= esc(old('state',   $prefill['state']   ?? '')) ?>" class="px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm">
                            <input name="pincode" required placeholder="Pincode *" value="<?= esc(old('pincode', $prefill['pincode'] ?? '')) ?>" class="sm:col-span-2 px-4 py-3 rounded-lg border-2 border-slate-200 focus:border-brand-400 focus:outline-none text-sm" inputmode="numeric" maxlength="6">
                        </div>
                    </section>
                <?php else: ?>
                    <section class="bg-amber-50 border-2 border-dashed border-amber-200 rounded-2xl p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">📩</span>
                            <div>
                                <h3 class="font-display text-base font-black">No shipping needed</h3>
                                <p class="text-sm text-slate-600 mt-0.5">
                                    Your cart has only digital / online / in-person items. We'll send access links + venue details to the contact info above.
                                </p>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- ===== Payment — adaptive radio cards ===== -->
                <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-soft">
                    <h2 class="font-display text-base sm:text-lg font-black flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-500 text-white text-xs"><?= ! empty($needsShipping) ? '3' : '2' ?></span>
                        Payment method
                    </h2>

                    <div class="mt-3 space-y-2">
                        <?php
                        // Order in which to render methods — primary actions first
                        $renderOrder = ['razorpay','phonepe','cod','partial_cod','partial_venue'];
                        foreach ($renderOrder as $mkey):
                            if (! isset($methods[$mkey])) continue;
                            $m = $methods[$mkey];
                        ?>
                            <label class="block rounded-xl border-2 transition cursor-pointer <?= $m['available'] ? '' : 'opacity-60 cursor-not-allowed' ?>"
                                   :class="payment === '<?= $mkey ?>' ? 'border-brand-500 bg-brand-50/50' : 'border-slate-200 hover:border-slate-300'">
                                <div class="flex items-start gap-3 p-3 sm:p-4">
                                    <input type="radio" name="payment_method" value="<?= $mkey ?>" x-model="payment"
                                           class="mt-1 accent-brand-500" <?= $m['available'] ? '' : 'disabled' ?>>
                                    <div class="text-2xl shrink-0 leading-none"><?= $m['icon'] ?></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-sm text-slate-900"><?= esc($m['label']) ?></div>
                                        <?php if ($m['available'] && ! empty($m['sub'])): ?>
                                            <div class="text-xs text-slate-600 mt-0.5"><?= esc($m['sub']) ?></div>
                                        <?php endif; ?>
                                        <?php if (! $m['available'] && ! empty($m['reason'])): ?>
                                            <div class="text-xs text-amber-700 mt-0.5">⚠ <?= esc($m['reason']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($mkey === 'partial_venue' && $m['available']): ?>
                                            <div class="mt-2 px-2.5 py-1.5 rounded-md bg-amber-50 border border-amber-200 text-[11px] text-amber-800">
                                                💡 <strong>How it works:</strong> Pay <strong><?= '₹' . number_format(round($m['advance']/100)) ?></strong> now to confirm your seat.
                                                Carry the remaining <strong><?= '₹' . number_format(round($m['balance']/100)) ?></strong> in cash / UPI to the <strong><?= esc($m['balance_at']) ?></strong>.
                                                Refundable up to 48h before.
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($mkey === 'cod' && $m['available']): ?>
                                            <div class="mt-1 text-[11px] text-slate-500">Additional COD fee ₹49 applies.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- ===== Summary ===== -->
            <aside class="bg-white rounded-2xl p-4 sm:p-5 h-fit lg:sticky lg:top-24 shadow-soft space-y-3 text-sm">
                <h3 class="font-display text-lg font-black">Order summary</h3>
                <ul class="space-y-1.5 text-xs">
                    <?php foreach ($items as $it): ?>
                        <li class="flex justify-between gap-3">
                            <span class="line-clamp-2"><?= esc($it['product_name']) ?> × <?= $it['qty'] ?></span>
                            <span class="font-semibold whitespace-nowrap"><?= kb_money((int)($it['line_total'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="border-t border-slate-100 pt-3 space-y-1">
                    <div class="flex justify-between"><span>Subtotal</span><span><?= kb_money((int)($cart['subtotal'])) ?></span></div>
                    <?php if ($cart['discount_total'] > 0): ?>
                        <div class="flex justify-between text-emerald-600"><span>Discount</span><span>− <?= kb_money((int)($cart['discount_total'])) ?></span></div>
                    <?php endif; ?>
                    <?php if (! empty($needsShipping)): ?>
                        <div class="flex justify-between"><span>Shipping</span><span><?= $cart['shipping_total'] > 0 ? kb_money((int) $cart['shipping_total']) : '<span class="text-emerald-600 font-bold">FREE</span>' ?></span></div>
                    <?php endif; ?>
                    <div x-show="payment === 'cod' || payment === 'partial_cod'" x-cloak class="flex justify-between text-amber-700"><span>COD fee</span><span>₹49</span></div>
                </div>

                <div class="border-t border-slate-200 pt-3">
                    <!-- Total + dynamic "what you pay NOW" breakout -->
                    <div class="flex justify-between text-lg font-black">
                        <span>Order total</span>
                        <span>₹<span x-text="((<?= (int) $cart['grand_total'] ?> + (['cod','partial_cod'].includes(payment) ? 4900 : 0)) / 100).toLocaleString('en-IN')"></span></span>
                    </div>

                    <!-- Adaptive "you pay now" hint -->
                    <div class="mt-2 px-3 py-2.5 rounded-lg bg-slate-50 text-xs">
                        <div x-show="payment === 'razorpay' || payment === 'phonepe'" class="flex items-center justify-between font-bold text-slate-900">
                            <span>Pay now</span>
                            <span class="text-brand-600">₹<span x-text="(<?= (int) $cart['grand_total'] ?> / 100).toLocaleString('en-IN')"></span></span>
                        </div>
                        <div x-show="payment === 'cod'" x-cloak class="space-y-1">
                            <div class="flex justify-between"><span>Pay now</span><span class="font-bold text-emerald-700">₹0</span></div>
                            <div class="flex justify-between text-slate-500"><span>Pay courier at door</span><span>₹<span x-text="((<?= (int) $cart['grand_total'] ?> + 4900) / 100).toLocaleString('en-IN')"></span></span></div>
                        </div>
                        <?php if (! empty($methods['partial_cod']['available'])): ?>
                            <div x-show="payment === 'partial_cod'" x-cloak class="space-y-1">
                                <div class="flex justify-between"><span>Pay now (online)</span><span class="font-bold text-brand-600">₹<?= number_format(round(($methods['partial_cod']['advance'] ?? 0)/100)) ?></span></div>
                                <div class="flex justify-between text-slate-500"><span>Pay at door</span><span>₹<?= number_format(round(($methods['partial_cod']['balance'] ?? 0)/100) + 49) ?></span></div>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($methods['partial_venue']['available'])): ?>
                            <div x-show="payment === 'partial_venue'" x-cloak class="space-y-1">
                                <div class="flex justify-between"><span>Pay now (online)</span><span class="font-bold text-brand-600">₹<?= number_format(round(($methods['partial_venue']['advance'] ?? 0)/100)) ?></span></div>
                                <div class="flex justify-between text-slate-500"><span>Pay at <?= esc($methods['partial_venue']['balance_at'] ?? 'venue') ?></span><span>₹<?= number_format(round(($methods['partial_venue']['balance'] ?? 0)/100)) ?></span></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="w-full h-12 mt-2 rounded-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-sm uppercase tracking-wider shadow-cta hover:shadow-cta-lg hover:-translate-y-0.5 transition">
                    <span x-show="payment === 'razorpay' || payment === 'phonepe'">Pay now →</span>
                    <span x-show="payment === 'cod'" x-cloak>Place order (COD) →</span>
                    <span x-show="payment === 'partial_cod'" x-cloak>Pay advance & confirm →</span>
                    <span x-show="payment === 'partial_venue'" x-cloak>Reserve seat →</span>
                </button>
                <p class="text-[10px] text-center text-slate-400">By placing this order you agree to Khoobie's terms.</p>

                <!-- Trust strip -->
                <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100 text-[10px] text-slate-600">
                    <div class="text-center"><div class="text-lg">🔒</div>Secure</div>
                    <div class="text-center"><div class="text-lg">↩️</div>7-day return</div>
                    <div class="text-center"><div class="text-lg">📞</div>Support 9-9</div>
                </div>
            </aside>
        </form>
    </div>
</section>

<script>
    if (window.kbTrack) window.kbTrack('InitiateCheckout', { value: <?= (int) $cart['grand_total'] / 100 ?>, currency: 'INR' });
</script>

<?= $this->endSection() ?>
