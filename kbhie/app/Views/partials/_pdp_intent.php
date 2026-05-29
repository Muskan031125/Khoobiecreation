<?php
/**
 * Type-aware intent capture block — drops in next to the buy box on PDPs that
 * don't convert through pure add-to-cart (tuition, course, meetup, service,
 * membership, digital, affiliate).
 *
 * Driven by a single config map: per product type → kind, button label, copy,
 * whether part-payment applies. The Alpine `kbIntent` component drives the
 * 3-step flow: form → OTP → success (or → pay → success).
 *
 * Inputs available from PDP scope:
 *   $product (full row, ProductController::show)
 */
$type    = $product['type'] ?? 'simple';
$productId = (int) $product['id'];

// What this PDP's intent CTA should say + capture
$config = match ($type) {
    'tuition'    => [
        'kind' => 'trial', 'label' => 'Book FREE trial class',
        'lead' => 'Free 1-hour trial · no card needed · cancel anytime',
        'icon' => '🎓', 'partPay' => 0, 'fields' => ['name','phone','child_age','preferred_slot'],
    ],
    'course'     => [
        'kind' => 'notify_me', 'label' => 'Get launch reminder + preview',
        'lead' => 'We\'ll email you when this batch opens',
        'icon' => '🎬', 'partPay' => 0, 'fields' => ['name','email'],
    ],
    'meetup'     => [
        'kind' => 'reserve_seat', 'label' => 'Reserve seat (pay ₹100 to block)',
        'lead' => 'Pay just ₹100 now to hold your spot · balance at the door',
        'icon' => '🎟️', 'partPay' => 10000, 'fields' => ['name','phone','child_age'],
    ],
    'service'    => [
        'kind' => 'discovery_call', 'label' => 'Book a free discovery call',
        'lead' => '15-min call with the mentor to plan your child\'s journey',
        'icon' => '☎️', 'partPay' => 0, 'fields' => ['name','phone','message'],
    ],
    'membership' => [
        'kind' => 'trial', 'label' => 'Start 7-day FREE trial',
        'lead' => 'Full access for 7 days · no card needed',
        'icon' => '⭐', 'partPay' => 0, 'fields' => ['name','email','phone'],
    ],
    'affiliate'  => null,   // outbound only — no intent capture
    'simple','variable','bundle' => null,  // cart-only
    default      => null,
};

if (! $config) return;
?>
<div class="mt-5 rounded-2xl border-2 border-dashed border-brand-200 bg-gradient-to-br from-brand-50 to-amber-50 p-4 sm:p-5"
     x-data="kbIntent({
        productId: <?= $productId ?>,
        kind:      <?= json_encode($config['kind']) ?>,
        amountDue: <?= (int) $config['partPay'] ?>,
        fields:    <?= json_encode($config['fields']) ?>,
     })"
     x-cloak>

    <!-- ===== Step 1: Pitch + form ===== -->
    <div x-show="step === 'form'" class="space-y-3">
        <div class="flex items-start gap-3">
            <span class="text-2xl shrink-0"><?= $config['icon'] ?></span>
            <div class="flex-1">
                <h3 class="font-display text-lg sm:text-xl font-black text-slate-900 leading-tight">Not ready to pay? <?= $type === 'tuition' || $type === 'membership' ? 'Try free.' : 'Save your spot.' ?></h3>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5"><?= esc($config['lead']) ?></p>
            </div>
        </div>

        <form @submit.prevent="submit()" class="space-y-2.5">
            <?php if (in_array('name', $config['fields'])): ?>
                <input type="text" x-model="form.name" placeholder="Parent's name *" required
                       class="w-full px-3 py-2.5 rounded-lg border-2 border-slate-200 bg-white focus:border-brand-400 focus:outline-none text-sm">
            <?php endif; ?>
            <?php if (in_array('phone', $config['fields'])): ?>
                <div class="flex">
                    <span class="px-3 inline-flex items-center bg-slate-100 border-2 border-r-0 border-slate-200 rounded-l-lg text-sm font-semibold text-slate-700">+91</span>
                    <input type="tel" x-model="form.phone" placeholder="10-digit mobile" maxlength="10" pattern="[6-9][0-9]{9}"
                           class="flex-1 px-3 py-2.5 rounded-r-lg border-2 border-slate-200 bg-white focus:border-brand-400 focus:outline-none text-sm">
                </div>
            <?php endif; ?>
            <?php if (in_array('email', $config['fields'])): ?>
                <input type="email" x-model="form.email" placeholder="Email address"
                       class="w-full px-3 py-2.5 rounded-lg border-2 border-slate-200 bg-white focus:border-brand-400 focus:outline-none text-sm">
            <?php endif; ?>
            <?php if (in_array('child_age', $config['fields'])): ?>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" x-model.number="form.child_age" min="2" max="18" placeholder="Child's age"
                           class="px-3 py-2.5 rounded-lg border-2 border-slate-200 bg-white focus:border-brand-400 focus:outline-none text-sm">
                    <?php if (in_array('preferred_slot', $config['fields'])): ?>
                        <select x-model="form.preferred_slot"
                                class="px-3 py-2.5 rounded-lg border-2 border-slate-200 bg-white focus:border-brand-400 focus:outline-none text-sm">
                            <option value="">Preferred time</option>
                            <option>Weekday evenings</option>
                            <option>Weekend mornings</option>
                            <option>Weekend afternoons</option>
                            <option>Flexible</option>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (in_array('message', $config['fields'])): ?>
                <textarea x-model="form.message" rows="2" placeholder="Any specific questions? (optional)"
                          class="w-full px-3 py-2.5 rounded-lg border-2 border-slate-200 bg-white focus:border-brand-400 focus:outline-none text-sm"></textarea>
            <?php endif; ?>

            <button type="submit"
                    :disabled="busy"
                    class="w-full h-11 rounded-lg bg-slate-900 hover:bg-brand-500 text-white font-bold text-sm uppercase tracking-wider shadow-sm hover:shadow-cta transition disabled:opacity-50">
                <span x-show="!busy"><?= esc($config['label']) ?></span>
                <span x-show="busy" x-cloak>Sending OTP…</span>
            </button>
            <p class="text-[10px] text-slate-500 text-center">
                We'll send a verification code via WhatsApp.<br>
                No spam, no card needed.
            </p>
        </form>
    </div>

    <!-- ===== Step 2: OTP verify ===== -->
    <div x-show="step === 'otp'" x-cloak class="space-y-3">
        <div class="flex items-start gap-3">
            <span class="text-2xl shrink-0">📲</span>
            <div class="flex-1">
                <h3 class="font-display text-lg font-black text-slate-900">Enter the 6-digit code</h3>
                <p class="text-xs text-slate-600 mt-0.5">Sent to <strong x-text="masked"></strong></p>
            </div>
        </div>
        <input type="text" x-model="otp" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="• • • • • •"
               class="w-full px-3 py-3 rounded-lg border-2 border-slate-200 bg-white focus:border-brand-400 focus:outline-none text-center text-2xl font-black tracking-[0.5em] tabular-nums">
        <div x-show="error" x-cloak class="text-xs text-rose-600 font-semibold" x-text="error"></div>
        <button @click="verify()" :disabled="busy || otp.length !== 6"
                class="w-full h-11 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold text-sm uppercase tracking-wider shadow-cta transition disabled:opacity-50">
            <span x-show="!busy">Verify &amp; continue →</span>
            <span x-show="busy" x-cloak>Verifying…</span>
        </button>
        <div class="flex items-center justify-between text-[11px] text-slate-500">
            <button type="button" @click="step='form'" class="hover:underline">← Change number</button>
            <button type="button" @click="resend()" :disabled="resendCooldown > 0" class="hover:underline disabled:opacity-50">
                <span x-show="resendCooldown === 0">Resend code</span>
                <span x-show="resendCooldown > 0" x-text="'Resend in ' + resendCooldown + 's'"></span>
            </button>
        </div>
    </div>

    <!-- ===== Step 3: Part-payment (only when amount_due > 0) ===== -->
    <div x-show="step === 'pay'" x-cloak class="space-y-3">
        <div class="flex items-start gap-3">
            <span class="text-2xl shrink-0">💳</span>
            <div class="flex-1">
                <h3 class="font-display text-lg font-black text-slate-900">Pay ₹<span x-text="(amountDue/100).toFixed(0)"></span> to lock your seat</h3>
                <p class="text-xs text-slate-600 mt-0.5">Refundable up to 48h before. Balance due at the venue.</p>
            </div>
        </div>
        <button @click="payNow()" :disabled="busy"
                class="w-full h-11 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm uppercase tracking-wider shadow-cta transition disabled:opacity-50">
            <span x-show="!busy">Pay ₹<span x-text="(amountDue/100).toFixed(0)"></span> via UPI / Card →</span>
            <span x-show="busy" x-cloak>Opening payment…</span>
        </button>
        <p class="text-[10px] text-slate-500 text-center">Powered by Razorpay · 100% secure</p>
    </div>

    <!-- ===== Step 4: Success ===== -->
    <div x-show="step === 'done'" x-cloak class="text-center py-3 space-y-2">
        <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 mx-auto inline-flex items-center justify-center text-2xl">✓</div>
        <h3 class="font-display text-lg font-black text-slate-900">You're in!</h3>
        <p class="text-sm text-slate-600" x-text="successMessage"></p>
        <p class="text-[11px] text-slate-500">Reference ID: <span class="font-mono" x-text="'KBI-' + (intentId || '').toString().padStart(6, '0')"></span></p>
    </div>
</div>
