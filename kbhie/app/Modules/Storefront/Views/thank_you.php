<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php
$method   = $order['payment_method'] ?? 'razorpay';
$paid     = (int) ($order['amount_paid'] ?? 0);
$due      = (int) ($order['amount_due']  ?? 0);
$grand    = (int) ($order['grand_total'] ?? 0);
$balanceAt= $order['balance_due_payable_at'] ?? null;

$copy = match($method) {
    'cod' => [
        'title'  => 'Order received',
        'kicker' => 'COD confirmed',
        'lead'   => "We'll call/WhatsApp " . esc($order['phone']) . " within a few hours to confirm before shipping.",
        'steps'  => [
            ['1', 'We confirm your address by phone'],
            ['2', 'We ship within 24h of confirmation'],
            ['3', 'You pay the courier at your door'],
        ],
        'icon'   => '📦',
    ],
    'partial_cod' => [
        'title'  => 'Advance received',
        'kicker' => 'Partial COD',
        'lead'   => 'Advance of ₹' . number_format(round($paid/100)) . ' captured. Balance ₹' . number_format(round($due/100)) . ' due on delivery.',
        'steps'  => [
            ['1', 'Order packed & shipped within 24h'],
            ['2', 'Courier brings it to your door'],
            ['3', 'Pay the balance to the courier'],
        ],
        'icon'   => '🚪',
    ],
    'partial_venue' => [
        'title'  => 'Seat reserved!',
        'kicker' => 'Booking confirmed',
        'lead'   => 'Advance of ₹' . number_format(round($paid/100)) . ' captured. Carry the balance ₹' . number_format(round($due/100)) . ' in cash/UPI to the ' . esc($balanceAt ?? 'venue') . '.',
        'steps'  => [
            ['1', 'We\'ll WhatsApp you the venue map + timing'],
            ['2', 'Show up 10 minutes early'],
            ['3', 'Pay balance at the ' . esc($balanceAt ?? 'venue') . ' (cash or UPI)'],
        ],
        'icon'   => '🎟️',
    ],
    default => [
        'title'  => 'Payment received!',
        'kicker' => 'Order paid',
        'lead'   => 'We\'ve emailed your invoice and access details.',
        'steps'  => [
            ['1', 'Order confirmation in your inbox'],
            ['2', 'Digital items unlock instantly · physical items ship in 24h'],
            ['3', 'Tracking link sent to ' . esc($order['phone'])],
        ],
        'icon'   => '🎉',
    ],
};
?>

<section class="py-12 sm:py-16 lg:py-20 bg-gradient-to-br from-emerald-50 via-amber-50 to-rose-50 min-h-[70vh]">
    <div class="mx-auto max-w-2xl px-4">

        <div class="bg-white rounded-3xl shadow-soft-lg p-6 sm:p-8 lg:p-10 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 text-3xl mb-4">
                <span><?= $copy['icon'] ?></span>
            </div>
            <span class="eyebrow text-emerald-700"><?= esc($copy['kicker']) ?></span>
            <h1 class="h-display text-3xl sm:text-4xl mt-1 text-slate-900"><?= esc($copy['title']) ?></h1>
            <p class="mt-2 text-sm sm:text-base text-slate-600 max-w-md mx-auto"><?= $copy['lead'] ?></p>
            <p class="mt-3 text-xs text-slate-500">Order ID · <span class="font-mono font-bold text-slate-900">#<?= esc($order['order_number']) ?></span></p>

            <!-- Payment breakdown -->
            <div class="mt-6 mx-auto max-w-xs grid grid-cols-2 gap-3 text-left">
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-[10px] uppercase tracking-wide font-bold text-slate-500">Paid now</div>
                    <div class="mt-1 text-xl font-black text-emerald-600 tabular-nums">₹<?= number_format(round($paid/100)) ?></div>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-[10px] uppercase tracking-wide font-bold text-slate-500">
                        <?= $due > 0 ? 'Balance · at ' . esc($balanceAt ?? 'delivery') : 'Total' ?>
                    </div>
                    <div class="mt-1 text-xl font-black <?= $due > 0 ? 'text-amber-700' : 'text-slate-900' ?> tabular-nums">
                        ₹<?= number_format(round(($due > 0 ? $due : $grand) / 100)) ?>
                    </div>
                </div>
            </div>

            <!-- What happens next -->
            <div class="mt-7 text-left">
                <h3 class="font-display font-black text-base text-slate-900">What happens next</h3>
                <ol class="mt-2 space-y-2">
                    <?php foreach ($copy['steps'] as $step): ?>
                        <li class="flex items-start gap-3">
                            <span class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-900 text-white text-xs font-black"><?= $step[0] ?></span>
                            <span class="text-sm text-slate-700"><?= $step[1] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <?php if (session('user')): ?>
                    <a href="<?= base_url('account/orders') ?>" class="btn-primary">View my orders</a>
                <?php else: ?>
                    <a href="<?= base_url('signup') ?>" class="btn-primary">Create account to track</a>
                <?php endif; ?>
                <a href="<?= base_url('shop') ?>" class="btn-ghost">Continue shopping</a>
            </div>
        </div>
    </div>
</section>

<script>
    // Universal Purchase event — fires across GA4, Meta Pixel, Google Ads via kbTrack + gtag
    if (window.kbTrack) {
        window.kbTrack('Purchase', {
            value:    <?= (int) $order['grand_total'] / 100 ?>,
            currency: 'INR',
            order_id: '<?= esc($order['order_number']) ?>'
        });
    }
    // Google Ads conversion (specific event_id format) — only if account configured
    <?php $gAdsId = env('tracking.google_ads_conversion_id', ''); $gAdsLabel = env('tracking.google_ads_conversion_label', ''); ?>
    <?php if ($gAdsId && $gAdsLabel): ?>
    if (window.gtag) {
        gtag('event', 'conversion', {
            'send_to': '<?= esc($gAdsId) ?>/<?= esc($gAdsLabel) ?>',
            'value': <?= (int) $order['grand_total'] / 100 ?>,
            'currency': 'INR',
            'transaction_id': '<?= esc($order['order_number']) ?>'
        });
    }
    <?php endif; ?>
</script>

<?= $this->endSection() ?>
