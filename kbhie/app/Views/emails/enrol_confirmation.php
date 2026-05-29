<?php
/**
 * Express-enrol confirmation — sent after /enrol/{vid}/pay completes.
 * Adapts copy on TWO axes:
 *   1) payment_method:  free_trial · partial_venue · prepaid (razorpay/phonepe)
 *   2) product_type:    tuition · course · meetup · service · membership
 *
 * Keeps the same visual rhythm as emails/order_placed.php so the customer
 * gets a consistent Khoobie look across order + booking flows.
 */
$method   = $payment_method ?? 'razorpay';
$type     = $product_type   ?? 'tuition';
$paid     = (int) ($amount_paid ?? 0);
$due      = (int) ($amount_due  ?? 0);
$amount   = (int) ($amount      ?? 0);

// ─── 1. Heading + banner branch by PAYMENT METHOD ───────────────────────────
$heading = match ($method) {
    'free_trial'    => '🎓 Your trial is booked!',
    'partial_venue' => '🎟️ Seat locked in — balance at the venue',
    default         => '🎉 You\'re enrolled!',
};

$banner = match ($method) {
    'free_trial'    => 'No card charged. Show up to the trial, try the format, then decide whether to continue. Cancel any time — no questions.',
    'partial_venue' => 'Advance of <strong>' . kb_money($paid > 0 ? $paid : ($amount - $due)) . '</strong> received ✓ Carry the balance <strong>' . kb_money($due) . '</strong> in cash or UPI to the venue.',
    default         => 'Payment of <strong>' . kb_money($amount) . '</strong> received ✓ Access details + welcome pack are on the way.',
};

$bannerColor = match ($method) {
    'free_trial'    => ['bg' => '#eef2ff', 'border' => '#6366f1'],
    'partial_venue' => ['bg' => '#fef3c7', 'border' => '#d97706'],
    default         => ['bg' => '#ecfdf5', 'border' => '#10b981'],
};

// ─── 2. "What happens next" branch by PRODUCT TYPE ──────────────────────────
$stepsByType = [
    'tuition' => [
        ['📅', 'Calendar invite + Zoom link arrive on email within an hour'],
        ['👩‍🏫', 'Instructor introduces themselves on WhatsApp before the first session'],
        ['🎓', 'Live classes start on the scheduled day · attend from anywhere'],
    ],
    'course' => [
        ['🔓', 'Course unlocked — log in and start watching now'],
        ['♾️', 'Lifetime access · learn at your own pace, replay any lesson'],
        ['🏆', 'Certificate auto-emails when you finish all lessons'],
    ],
    'meetup' => [
        ['📍', 'Venue map + exact timing arrive on WhatsApp within an hour'],
        ['⏰', 'Show up 10 min early · we save your seat'],
        ['🤝', 'Your Khoobie host greets you at the door'],
    ],
    'service' => [
        ['📞', 'Provider sends a short alignment call before the session'],
        ['🕐', 'Session happens at your booked slot · join from the link we email'],
        ['✍️', 'Written feedback + next-steps document after the session'],
    ],
    'membership' => [
        ['✨', 'All perks unlock instantly — check your dashboard'],
        ['🎁', 'Bonus: 200 Khoobie points credited to your account'],
        ['⏸️', 'Pause or cancel any time from My Subscriptions'],
    ],
];
$steps = $stepsByType[$type] ?? [
    ['📧', 'Confirmation in your inbox · check spam if not visible'],
    ['💬', 'Khoobie team in touch within 24h with next steps'],
];

// Free trials add a trial-specific final step regardless of type
if ($method === 'free_trial') {
    $steps = [
        ['🎓', 'Calendar invite + Zoom link arrive on email within an hour'],
        ['👋', 'Show up for the trial · meet the instructor, try the format'],
        ['💛', 'Continue? You\'ll pay then. Walk away? No charges, no questions.'],
    ];
}

// ─── 3. Render ───────────────────────────────────────────────────────────────
$confirmedUrl = base_url('enrol/confirmed/' . $order_number);

$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">' . $heading . '</h2>';
$body .= '<p>Hi ' . esc($name) . ', your booking for <strong>' . esc($product_name) . '</strong> is confirmed.</p>';
$body .= '<p>Booking ID: <strong>#' . esc($order_number) . '</strong></p>';

$body .= '<p style="background:' . $bannerColor['bg'] . ';border-left:4px solid ' . $bannerColor['border'] . ';padding:14px;border-radius:6px;font-size:14px;">' . $banner . '</p>';

// What happens next
$body .= '<h3 style="margin:24px 0 10px;font-size:16px;">What happens next</h3>';
$body .= '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;">';
foreach ($steps as $i => $step) {
    $body .= '<tr>'
           . '<td style="vertical-align:top;padding:8px 12px 8px 0;width:32px;font-size:20px;line-height:1;">' . $step[0] . '</td>'
           . '<td style="vertical-align:top;padding:8px 0;font-size:14px;color:#334155;">' . esc($step[1]) . '</td>'
           . '</tr>';
}
$body .= '</table>';

// Payment breakdown if part-pay-at-venue
if ($method === 'partial_venue' && $due > 0) {
    $advance = $amount - $due;
    $body .= '<h3 style="margin:24px 0 10px;font-size:16px;">Payment breakdown</h3>';
    $body .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;">'
           . '<tr><td style="padding:6px 0;color:#10b981;">Paid now (advance)</td><td style="padding:6px 0;text-align:right;color:#10b981;">' . kb_money($advance) . '</td></tr>'
           . '<tr><td style="padding:6px 0;color:#d97706;">Due at the venue</td><td style="padding:6px 0;text-align:right;color:#d97706;">' . kb_money($due) . '</td></tr>'
           . '<tr><td style="padding:12px 0;font-weight:bold;border-top:1px solid #e2e8f0;">Total</td><td style="padding:12px 0;text-align:right;font-weight:bold;border-top:1px solid #e2e8f0;">' . kb_money($amount) . '</td></tr>'
           . '</table>';
}

$body .= '<p style="margin-top:24px;"><a href="' . esc($confirmedUrl) . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">View booking</a></p>';

$body .= '<p style="font-size:13px;color:#64748b;margin-top:18px;">Questions? Reply to this email or WhatsApp us at +91 88992 23300.</p>';

echo $this->include('emails/_layout', ['subject' => $subject ?? 'You\'re in!', 'body' => $body]);
