<?php
/**
 * Adaptive order confirmation — branches by payment_method so each customer
 * gets clear, accurate, payment-specific instructions.
 */
$items_html = '';
foreach (($items ?? []) as $i) {
    $items_html .= '<tr><td style="padding:8px 0;border-bottom:1px solid #f1f5f9;">' . esc($i['name']) . ' × ' . (int) $i['qty']
                 . '</td><td style="padding:8px 0;border-bottom:1px solid #f1f5f9;text-align:right;">' . kb_money((int) $i['line_total']) . '</td></tr>';
}

$method      = $payment_method ?? 'razorpay';
$paid        = (int) ($amount_paid ?? 0);
$due         = (int) ($amount_due  ?? 0);
$balanceAt   = $balance_due_payable_at ?? null;

$heading = match ($method) {
    'cod'           => '📦 Order received — we\'ll confirm by phone',
    'partial_cod'   => '✓ Advance received — rest at delivery',
    'partial_venue' => '🎟️ Seat reserved — balance at the ' . esc($balanceAt ?: 'venue'),
    default         => '🎉 Payment received — order confirmed',
};

$banner = match ($method) {
    'cod' => 'We\'ll call/WhatsApp you on <strong>' . esc($phone) . '</strong> within a few hours to confirm the address before shipping. Have <strong>' . kb_money((int) $amount) . '</strong> ready in cash for the courier.',
    'partial_cod' => 'Advance of <strong>' . kb_money($paid) . '</strong> captured ✓ Balance <strong>' . kb_money($due) . '</strong> due to the courier on delivery (cash or UPI). We\'ll ship within 24h.',
    'partial_venue' => 'Advance of <strong>' . kb_money($paid) . '</strong> captured ✓ Carry the balance <strong>' . kb_money($due) . '</strong> in cash or UPI to the <strong>' . esc($balanceAt ?: 'venue') . '</strong>. Refundable up to 48h before.',
    default => 'Payment received. We\'ve emailed your invoice. Digital items unlock instantly; physical items ship in 24h.',
};

$bannerColor = match ($method) {
    'cod'           => ['bg' => '#fffbeb', 'border' => '#f59e0b'],
    'partial_cod'   => ['bg' => '#eff6ff', 'border' => '#3b82f6'],
    'partial_venue' => ['bg' => '#fef3c7', 'border' => '#d97706'],
    default         => ['bg' => '#ecfdf5', 'border' => '#10b981'],
};

$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">' . $heading . '</h2>';
$body .= '<p>Hi ' . esc($name) . ', we\'ve received your order <strong>#' . esc($order_number) . '</strong>' . (isset($placed_at) ? ' placed on ' . kb_date($placed_at, true) : '') . '.</p>';

$body .= '<p style="background:' . $bannerColor['bg'] . ';border-left:4px solid ' . $bannerColor['border'] . ';padding:14px;border-radius:6px;font-size:14px;">' . $banner . '</p>';

$body .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:20px;font-size:14px;">'
       . '<thead><tr><th align="left" style="padding:8px 0;border-bottom:2px solid #0f172a;">Item</th><th align="right" style="padding:8px 0;border-bottom:2px solid #0f172a;">Amount</th></tr></thead>'
       . '<tbody>' . $items_html . '</tbody>'
       . '<tfoot>';

if ($paid > 0 && $due > 0) {
    $body .= '<tr><td style="padding:6px 0;color:#10b981;">Paid now</td><td style="padding:6px 0;text-align:right;color:#10b981;">' . kb_money($paid) . '</td></tr>';
    $body .= '<tr><td style="padding:6px 0;color:#d97706;">Balance at ' . esc($balanceAt ?: 'venue') . '</td><td style="padding:6px 0;text-align:right;color:#d97706;">' . kb_money($due) . '</td></tr>';
}
$body .= '<tr><td style="padding:12px 0;font-weight:bold;">Total</td><td style="padding:12px 0;font-weight:bold;text-align:right;font-size:18px;">' . kb_money((int) $amount) . '</td></tr>';
$body .= '</tfoot></table>';

$body .= '<p style="margin-top:24px;"><a href="' . esc($order_url) . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">View order</a></p>';

// Method-specific next steps
$nextSteps = match ($method) {
    'cod' => '<p style="font-size:13px;color:#64748b;"><strong>Next:</strong> Address confirmation call → packed → shipped → delivered → pay courier.</p>',
    'partial_venue' => '<p style="font-size:13px;color:#64748b;"><strong>Next:</strong> We\'ll WhatsApp you the venue map &amp; timing → show up 10 min early → pay balance at the ' . esc($balanceAt ?: 'venue') . '.</p>',
    default => '',
};
$body .= $nextSteps;

$body .= '<p style="font-size:13px;color:#64748b;">Questions? Reply to this email or WhatsApp us at +91 88992 23300.</p>';

echo $this->include('emails/_layout', ['subject' => $subject ?? 'Order received', 'body' => $body]);
