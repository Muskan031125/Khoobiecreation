<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">🎟️ Your seat is locked in!</h2>';
$body .= '<p>Hi ' . esc($name) . ',</p>';
$body .= '<p>You\'re confirmed for <strong>' . esc($product_name) . '</strong>.</p>';
$body .= '<p style="background:#fef3c7;border-left:4px solid #d97706;padding:14px;border-radius:6px;font-size:14px;">'
       . '<strong>✓ Advance paid:</strong> ₹' . number_format(round(($amount_paid ?? 0)/100)) . '<br>'
       . '<strong>💰 Balance at venue:</strong> ₹' . number_format(round(($amount_due ?? 0)/100)) . ' (cash or UPI)<br>'
       . '<strong>📅 When:</strong> ' . esc($when ?? 'TBC') . '<br>'
       . '<strong>📍 Where:</strong> ' . esc($where ?? 'TBC') . '</p>';
$body .= '<p style="margin-top:18px;"><strong>What to know:</strong></p>';
$body .= '<ul style="font-size:14px;color:#475569;"><li>Arrive 10 minutes early to settle in</li><li>Carry the balance in cash or UPI-ready</li><li>Materials provided — just show up!</li><li>Cancel up to 48h before for a full refund</li></ul>';
if (! empty($maps_url)) {
    $body .= '<p style="margin-top:24px;"><a href="' . esc($maps_url) . '" target="_blank" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">🗺️ Open in Maps</a></p>';
}
$body .= '<p style="font-size:13px;color:#64748b;">Questions? Reply or WhatsApp +91 88992 23300.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Seat reserved', 'body' => $body]);
