<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<?php
$snap = json_decode($item['product_snapshot'] ?? '{}', true) ?: [];
$type = $snap['type'] ?? 'simple';
$method = $order['payment_method'] ?? '';

$copy = match($method) {
    'free_trial' => [
        'title'  => 'Your trial is booked!',
        'kicker' => '🎓 Trial booked',
        'lead'   => 'You\'ll get your first session details on WhatsApp + email — no card was charged.',
        'icon'   => '🎓',
        'steps'  => [
            ['1', 'We\'ll WhatsApp you the session link / venue details by tomorrow'],
            ['2', 'Show up for the trial — try the format, meet the instructor'],
            ['3', 'Continue? You\'ll pay then. Walk away? No charges, no questions.'],
        ],
    ],
    'partial_venue' => [
        'title'  => 'Your seat is locked in!',
        'kicker' => '🎟️ Booking confirmed',
        'lead'   => 'Advance received. Carry the balance in cash / UPI to the venue.',
        'icon'   => '🎟️',
        'steps'  => [
            ['1', 'We\'ll WhatsApp you the venue map + timing'],
            ['2', 'Show up 10 min early'],
            ['3', 'Pay balance at the door — cash or UPI'],
        ],
    ],
    default => [
        'title'  => 'You\'re in!',
        'kicker' => '🎉 Enrolment confirmed',
        'lead'   => 'Payment received. Access details + welcome pack on the way.',
        'icon'   => '🎉',
        'steps'  => match($type) {
            'tuition'    => [['1','Check your email for class link + schedule'], ['2','Calendar invite arrives separately'], ['3','Instructor introduces themselves on WhatsApp']],
            'course'     => [['1','Course unlocked — start watching now'], ['2','Lifetime access · learn at your own pace'], ['3','Certificate emailed on completion']],
            'meetup'     => [['1','Venue map + timing on WhatsApp'], ['2','Arrive 10 min early'], ['3','Khoobie host meets you at the door']],
            'service'    => [['1','Provider sends a pre-session call to align'], ['2','Session at your booked slot'], ['3','Written feedback + next steps after']],
            'membership' => [['1','All perks unlock instantly'], ['2','Cancel any time from My Subscriptions'], ['3','Bonus: 200 Khoobie points credited']],
            default      => [['1','Confirmation in your inbox'], ['2','Khoobie team in touch within 24h']],
        },
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
            <p class="mt-2 text-sm sm:text-base text-slate-600 max-w-md mx-auto"><?= esc($copy['lead']) ?></p>

            <div class="mt-5 text-xs text-slate-500">
                Booking ID · <span class="font-mono font-bold text-slate-900">#<?= esc($order['order_number']) ?></span>
            </div>

            <div class="mt-4 px-4 py-3 rounded-xl bg-slate-50 inline-block">
                <div class="text-xs uppercase tracking-wider font-bold text-slate-500"><?= esc($snap['type'] ?? 'Booking') ?></div>
                <div class="mt-0.5 font-display font-black"><?= esc($snap['name'] ?? '') ?></div>
            </div>

            <div class="mt-7 text-left">
                <h3 class="font-display font-black text-base">What happens next</h3>
                <ol class="mt-2 space-y-2">
                    <?php foreach ($copy['steps'] as $step): ?>
                        <li class="flex items-start gap-3">
                            <span class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-900 text-white text-xs font-black"><?= $step[0] ?></span>
                            <span class="text-sm text-slate-700"><?= esc($step[1]) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <?php if (session('user')): ?>
                    <a href="<?= base_url('account/orders') ?>" class="btn-primary">View my bookings</a>
                <?php else: ?>
                    <a href="<?= base_url('signup') ?>" class="btn-primary">Create account to track</a>
                <?php endif; ?>
                <a href="<?= base_url('classes') ?>" class="btn-ghost">Browse more classes</a>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
