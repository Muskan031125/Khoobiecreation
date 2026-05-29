<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-12 lg:py-20 bg-slate-50 min-h-[70vh]">
    <div class="mx-auto max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-lg p-6 lg:p-8">
            <h1 class="text-2xl font-black text-center">Create your account</h1>
            <p class="mt-1 text-center text-sm text-slate-600">Earn 100 welcome points instantly &#127873;</p>

            <?php if (session('error')): ?>
                <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"><?= esc(session('error')) ?></div>
            <?php endif; ?>
            <?php if (session('errors')): ?>
                <ul class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm list-disc ml-5">
                    <?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="<?= base_url('signup') ?>" class="mt-6 space-y-3">
                <?= csrf_field() ?>
                <?= $this->include('partials/_honeypot') ?>
                <input name="name"  required placeholder="Your name"          value="<?= esc(old('name', $prefill['name'] ?? '')) ?>"  class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-brand-400 focus:outline-none">
                <input name="email" required type="email"   placeholder="Email" value="<?= esc(old('email', $prefill['email'] ?? '')) ?>" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-brand-400 focus:outline-none">
                <input name="phone" required type="tel"     placeholder="Phone (WhatsApp)" value="<?= esc(old('phone', $prefill['phone'] ?? '')) ?>" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-brand-400 focus:outline-none">
                <input name="password" required type="password" placeholder="Create a password (min 6 chars)" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-brand-400 focus:outline-none">

                <button class="w-full px-6 py-3 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold shadow-cta">Create account & claim 100 points</button>
                <p class="text-xs text-slate-500 text-center">By signing up you agree to receive order updates and offers from Krafty Khoobie.</p>
            </form>

            <p class="mt-5 text-center text-sm text-slate-600">
                Already a member? <a href="<?= base_url('login') ?>" class="text-brand-600 font-semibold hover:underline">Log in</a>
            </p>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
