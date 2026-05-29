<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-20 lg:py-28 bg-gradient-to-br from-emerald-50 to-amber-50 text-center">
    <div class="mx-auto max-w-md px-4">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500 text-white text-3xl">✓</div>
        <h1 class="mt-4 text-3xl font-black">You're in!</h1>
        <p class="mt-2 text-slate-600">We've sent your discount code and a little surprise to your inbox & WhatsApp.</p>
        <p class="mt-1 text-sm text-slate-500">Use code <span class="font-mono font-bold bg-white px-2 py-1 rounded">WELCOME10</span> at checkout for 10% off.</p>

        <div class="mt-6 flex justify-center gap-3">
            <a href="<?= base_url('shop') ?>" class="btn-primary">Start shopping &rarr;</a>
            <a href="<?= base_url('/') ?>" class="btn-ghost">Back to home</a>
        </div>
    </div>
</section>

<script>
    if (window.kbTrack) window.kbTrack('CompleteRegistration', { content_name: 'lead-capture' });
</script>

<?= $this->endSection() ?>
