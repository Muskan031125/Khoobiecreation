<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-12 lg:py-20 bg-slate-50 min-h-[70vh]">
    <div class="mx-auto max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-lg p-6 lg:p-8">
            <h1 class="text-2xl font-black text-center">Welcome back</h1>
            <p class="mt-1 text-center text-sm text-slate-600">Log in to continue your screen-free journey.</p>

            <?php if (session('error')): ?>
                <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"><?= esc(session('error')) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login') ?>" class="mt-6 space-y-3" x-data="{ mode: 'password' }">
                <?= csrf_field() ?>
                <?= $this->include('partials/_honeypot') ?>
                <input type="hidden" name="next" value="<?= esc($next ?? '/account') ?>">

                <div class="flex gap-2 text-sm">
                    <button type="button" @click="mode = 'password'" :class="mode === 'password' ? 'bg-brand-500 text-white' : 'bg-slate-100'" class="flex-1 px-3 py-2 rounded-md font-semibold">Password</button>
                    <button type="button" @click="mode = 'otp'" :class="mode === 'otp' ? 'bg-brand-500 text-white' : 'bg-slate-100'" class="flex-1 px-3 py-2 rounded-md font-semibold">OTP</button>
                </div>

                <input name="identifier" required placeholder="Email or phone" value="<?= esc(old('identifier')) ?>" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-brand-400 focus:outline-none">

                <template x-if="mode === 'password'">
                    <div class="space-y-3">
                        <input name="password" type="password" placeholder="Password" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-brand-400 focus:outline-none">
                        <button class="w-full px-6 py-3 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold">Log in</button>
                    </div>
                </template>

                <template x-if="mode === 'otp'">
                    <div x-data="otpFlow()" class="space-y-3">
                        <button type="button" @click="send($refs.idInput.value)" x-show="!sent" class="w-full px-6 py-3 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold">Send OTP</button>
                        <template x-if="sent">
                            <div class="space-y-3">
                                <input x-model="code" placeholder="6-digit code" inputmode="numeric" maxlength="6" class="w-full px-4 py-3 rounded-lg border border-slate-200 text-center text-xl tracking-widest">
                                <p class="text-xs text-slate-500" x-text="message"></p>
                                <button type="button" @click="verify($refs.idInput.value)" class="w-full px-6 py-3 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold">Verify & log in</button>
                            </div>
                        </template>
                    </div>
                </template>
            </form>

            <p class="mt-5 text-center text-sm text-slate-600">
                New here? <a href="<?= base_url('signup') ?>" class="text-brand-600 font-semibold hover:underline">Create an account</a>
            </p>
        </div>
    </div>
</section>

<script>
    // Add an alpine ref to the identifier input so OTP flow can read it
    document.addEventListener('alpine:init', () => {
        Alpine.data('otpFlow', () => ({
            sent: false,
            code: '',
            message: '',
            async send(phone) {
                if (!phone) { this.message = 'Enter your phone first.'; return }
                const fd = new FormData(); fd.append('phone', phone);
                fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                const r = await fetch('<?= base_url('otp/send') ?>', { method: 'POST', body: fd });
                const j = await r.json();
                this.sent = j.ok;
                this.message = j.ok ? (j.message + (j.dev_code ? ' (dev code: ' + j.dev_code + ')' : '')) : j.error;
            },
            async verify(phone) {
                const fd = new FormData(); fd.append('phone', phone); fd.append('code', this.code);
                fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                const r = await fetch('<?= base_url('otp/verify') ?>', { method: 'POST', body: fd });
                const j = await r.json();
                if (j.ok) location.href = j.redirect; else this.message = j.error;
            }
        }))
    })
    // Tag the identifier input
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('input[name="identifier"]')?.setAttribute('x-ref', 'idInput')
    })
</script>

<?= $this->endSection() ?>
