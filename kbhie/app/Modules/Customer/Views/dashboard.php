<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-8 lg:py-12 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4">
        <div class="grid lg:grid-cols-[260px_1fr] gap-6">
            <?= $this->include('App\Modules\Customer\Views\_account_nav') ?>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h1 class="text-2xl font-black">Hi <?= esc($user['name'] ?: $user['email']) ?> &#128075;</h1>
                    <p class="text-slate-600 text-sm mt-1">Welcome back — here's your screen-free dashboard.</p>
                </div>

                <?php
                $phoneVerified = ! empty($verify['phone_verified_at']);
                $emailVerified = ! empty($verify['email_verified_at']);
                if (! $phoneVerified || ! $emailVerified):
                ?>
                <!-- Verification banner — disappears when both are verified -->
                <div class="bg-gradient-to-r from-brand-50 to-amber-50 border border-brand-200 rounded-2xl p-5"
                     x-data="verifyPanel()" x-cloak>
                    <div class="flex items-start gap-3">
                        <div class="text-3xl">🎁</div>
                        <div class="flex-1">
                            <h3 class="font-black text-lg">Verify &amp; earn rewards</h3>
                            <p class="mt-1 text-sm text-slate-700">Verify your phone and email to claim <strong>up to 250 Khoobie Points</strong> plus two personal coupons (10% off + 5% off).</p>
                            <div class="mt-3 grid sm:grid-cols-2 gap-3">

                                <!-- Phone block -->
                                <div class="bg-white rounded-xl p-4 border border-slate-100">
                                    <?php if ($phoneVerified): ?>
                                        <div class="flex items-center gap-2 text-sm font-semibold text-emerald-700">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Phone verified ✓
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">Reward claimed.</p>
                                    <?php else: ?>
                                        <div class="text-xs uppercase tracking-wide font-bold text-slate-500">Verify phone</div>
                                        <div class="mt-1 text-sm text-slate-700">+100 pts &amp; 10% off coupon</div>
                                        <template x-if="phone.step === 'idle'">
                                            <button type="button" @click="sendPhone()" :disabled="phone.busy"
                                                    class="mt-3 w-full px-3 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold">
                                                <span x-text="phone.busy ? 'Sending…' : 'Send OTP'"></span>
                                            </button>
                                        </template>
                                        <template x-if="phone.step === 'sent'">
                                            <div class="mt-3 space-y-2">
                                                <input x-model="phone.code" placeholder="6-digit code" maxlength="6" inputmode="numeric"
                                                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-center text-lg tracking-widest font-mono">
                                                <p class="text-[11px] text-slate-500" x-text="phone.message"></p>
                                                <button type="button" @click="confirmPhone()" :disabled="phone.busy"
                                                        class="w-full px-3 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold">
                                                    Verify &amp; claim reward
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="phone.step === 'done'">
                                            <div class="mt-3 p-3 bg-emerald-50 rounded text-sm text-emerald-800">
                                                <div class="font-bold">✓ Verified!</div>
                                                <div x-text="phone.reward"></div>
                                            </div>
                                        </template>
                                    <?php endif; ?>
                                </div>

                                <!-- Email block -->
                                <div class="bg-white rounded-xl p-4 border border-slate-100">
                                    <?php if ($emailVerified): ?>
                                        <div class="flex items-center gap-2 text-sm font-semibold text-emerald-700">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Email verified ✓
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">Reward claimed.</p>
                                    <?php else: ?>
                                        <div class="text-xs uppercase tracking-wide font-bold text-slate-500">Verify email</div>
                                        <div class="mt-1 text-sm text-slate-700">+50 pts &amp; 5% off coupon</div>
                                        <template x-if="email.step === 'idle'">
                                            <button type="button" @click="sendEmail()" :disabled="email.busy"
                                                    class="mt-3 w-full px-3 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold">
                                                <span x-text="email.busy ? 'Sending…' : 'Send OTP'"></span>
                                            </button>
                                        </template>
                                        <template x-if="email.step === 'sent'">
                                            <div class="mt-3 space-y-2">
                                                <input x-model="email.code" placeholder="6-digit code" maxlength="6" inputmode="numeric"
                                                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-center text-lg tracking-widest font-mono">
                                                <p class="text-[11px] text-slate-500" x-text="email.message"></p>
                                                <button type="button" @click="confirmEmail()" :disabled="email.busy"
                                                        class="w-full px-3 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold">
                                                    Verify &amp; claim reward
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="email.step === 'done'">
                                            <div class="mt-3 p-3 bg-emerald-50 rounded text-sm text-emerald-800">
                                                <div class="font-bold">✓ Verified!</div>
                                                <div x-text="email.reward"></div>
                                            </div>
                                        </template>
                                    <?php endif; ?>
                                </div>

                            </div>
                            <?php if (! $phoneVerified && ! $emailVerified): ?>
                                <p class="mt-3 text-xs text-slate-500 italic">Verify both and unlock an extra <strong>100-point bonus</strong>!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (! empty($personalCoupons)): ?>
                <!-- Personal coupons -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="font-black">Your coupons</h2>
                    <div class="mt-3 space-y-2">
                        <?php foreach ($personalCoupons as $c): ?>
                            <div class="flex items-center justify-between p-3 border border-dashed border-brand-200 rounded-lg bg-brand-50">
                                <div>
                                    <div class="font-mono font-black text-brand-700"><?= esc($c['code']) ?></div>
                                    <div class="text-xs text-slate-500">Single use · expires <?= kb_date($c['ends_at']) ?></div>
                                </div>
                                <button type="button" onclick="navigator.clipboard.writeText('<?= esc($c['code']) ?>');this.textContent='Copied!'" class="px-3 py-1.5 rounded-md bg-white border border-slate-200 hover:bg-slate-50 text-xs font-bold">Copy</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-brand-500 to-brand-600 text-white rounded-2xl p-5">
                        <div class="text-xs uppercase tracking-wide opacity-80">Khoobie Points</div>
                        <div class="text-3xl font-black mt-1"><?= number_format((int) $loyalty['points_balance']) ?></div>
                        <div class="text-xs mt-1 opacity-80"><?= ucfirst($loyalty['tier']) ?> tier</div>
                    </div>
                    <div class="bg-white rounded-2xl p-5 border border-slate-100">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Recent orders</div>
                        <div class="text-3xl font-black mt-1"><?= count($recentOrders) ?></div>
                        <a href="<?= base_url('account/orders') ?>" class="text-xs text-brand-600 font-semibold hover:underline">View all &rarr;</a>
                    </div>
                    <div class="bg-white rounded-2xl p-5 border border-slate-100">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Refer a friend</div>
                        <div class="text-lg font-bold mt-1">Earn 200 pts each</div>
                        <a href="<?= base_url('account/referrals') ?>" class="text-xs text-brand-600 font-semibold hover:underline">Get my link &rarr;</a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold">Recent orders</h2>
                        <a href="<?= base_url('account/orders') ?>" class="text-sm text-brand-600 font-semibold">All orders &rarr;</a>
                    </div>
                    <?php if (empty($recentOrders)): ?>
                        <p class="mt-3 text-sm text-slate-500">No orders yet. <a href="<?= base_url('shop') ?>" class="text-brand-600 font-semibold">Start shopping &rarr;</a></p>
                    <?php else: ?>
                        <ul class="mt-3 divide-y divide-slate-100">
                            <?php foreach ($recentOrders as $o): ?>
                            <li class="py-3 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-sm">#<?= esc($o['order_number']) ?></div>
                                    <div class="text-xs text-slate-500"><?= esc($o['status']) ?> · <?= kb_date($o['created_at']) ?></div>
                                </div>
                                <div class="font-bold"><?= kb_money((int)($o['grand_total'])) ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function verifyPanel() {
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';
    const post = async (url, body = {}) => {
        const fd = new FormData();
        for (const [k, v] of Object.entries(body)) fd.append(k, v);
        fd.append(csrfName, csrfHash);
        const r = await fetch(url, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        return r.json();
    };
    return {
        phone: { step: 'idle', code: '', message: '', busy: false, reward: '' },
        email: { step: 'idle', code: '', message: '', busy: false, reward: '' },

        async sendPhone() {
            this.phone.busy = true;
            const j = await post('<?= base_url('account/verify/phone/send') ?>');
            this.phone.busy = false;
            if (!j.ok) { alert(j.error); return }
            this.phone.step = 'sent';
            this.phone.message = j.message + (j.dev_code ? ' · dev code ' + j.dev_code : '');
            if (window.kbTrack) window.kbTrack('VerifyPhoneOtpSent');
        },
        async confirmPhone() {
            this.phone.busy = true;
            const j = await post('<?= base_url('account/verify/phone/confirm') ?>', { code: this.phone.code });
            this.phone.busy = false;
            if (!j.ok) { this.phone.message = j.error; return }
            this.phone.step  = 'done';
            this.phone.reward = j.reward && j.reward.message ? j.reward.message : 'Reward claimed!';
            if (window.kbTrack) window.kbTrack('VerifyPhoneSuccess');
            setTimeout(() => location.reload(), 2500);
        },
        async sendEmail() {
            this.email.busy = true;
            const j = await post('<?= base_url('account/verify/email/send') ?>');
            this.email.busy = false;
            if (!j.ok) { alert(j.error); return }
            this.email.step = 'sent';
            this.email.message = j.message + (j.dev_code ? ' · dev code ' + j.dev_code : '');
            if (window.kbTrack) window.kbTrack('VerifyEmailOtpSent');
        },
        async confirmEmail() {
            this.email.busy = true;
            const j = await post('<?= base_url('account/verify/email/confirm') ?>', { code: this.email.code });
            this.email.busy = false;
            if (!j.ok) { this.email.message = j.error; return }
            this.email.step   = 'done';
            this.email.reward = j.reward && j.reward.message ? j.reward.message : 'Reward claimed!';
            if (window.kbTrack) window.kbTrack('VerifyEmailSuccess');
            setTimeout(() => location.reload(), 2500);
        },
    };
}
</script>

<?= $this->endSection() ?>
