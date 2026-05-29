<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-6 sm:py-10 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-3xl px-3 sm:px-4 lg:px-6">
        <?= view('App\Modules\Customer\Views\_account_nav') ?>

        <span class="eyebrow text-slate-600">👤 You</span>
        <h1 class="h-display text-2xl sm:text-3xl mt-1">Profile</h1>

        <?php if (session('success')): ?>
            <div class="mt-3 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (session('errors')): ?>
            <ul class="mt-3 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm list-disc ml-5">
                <?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="<?= base_url('account/profile/update') ?>" class="mt-5 bg-white rounded-2xl shadow-soft p-5 sm:p-6 space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Full name *</label>
                <input name="name" required value="<?= esc($user['name'] ?? '') ?>" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
            </div>
            <div>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Email *
                    <?php if (! empty($user['email_verified_at'])): ?>
                        <span class="ml-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded">✓ Verified</span>
                    <?php else: ?>
                        <span class="ml-1 text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">Unverified</span>
                    <?php endif; ?>
                </label>
                <input name="email" type="email" required value="<?= esc($user['email'] ?? '') ?>" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
                <?php if (empty($user['email_verified_at'])): ?>
                    <button type="button"
                            class="mt-1 text-[11px] font-bold text-brand-600 hover:underline"
                            onclick="fetch('<?= base_url('account/verify/email/send') ?>', {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: new URLSearchParams({'<?= csrf_token() ?>':'<?= csrf_hash() ?>'})}).then(r=>r.json()).then(j=>alert(j.ok?'OTP sent to your email':'Could not send'))">
                        Resend verification OTP →
                    </button>
                <?php endif; ?>
            </div>
            <div>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Phone *
                    <?php if (! empty($user['phone_verified_at'])): ?>
                        <span class="ml-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded">✓ Verified</span>
                    <?php else: ?>
                        <span class="ml-1 text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">Unverified</span>
                    <?php endif; ?>
                </label>
                <input name="phone" type="tel" required value="<?= esc($user['phone'] ?? '') ?>" maxlength="10" class="mt-1 w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none">
                <p class="text-[10px] text-slate-500 mt-1">Changing email/phone will reset verification.</p>
            </div>

            <button type="submit" class="btn-primary mt-2">Save changes</button>
        </form>

        <!-- Security section -->
        <div class="mt-4 bg-white rounded-2xl shadow-soft p-5">
            <h2 class="font-display font-black">🔒 Security</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li class="flex justify-between"><span>Member since</span><span class="text-slate-500"><?= ! empty($user['created_at']) ? kb_date($user['created_at']) : '—' ?></span></li>
                <li class="flex justify-between"><span>Last login</span><span class="text-slate-500"><?= ! empty($user['last_login_at']) ? kb_date($user['last_login_at'], true, 'short') : '—' ?></span></li>
            </ul>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
