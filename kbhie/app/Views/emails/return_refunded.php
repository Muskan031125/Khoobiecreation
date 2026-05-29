<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">💸 Refund processed</h2>';
$body .= '<p>Hi ' . esc($name) . ', your refund for return <strong>' . esc($return_number) . '</strong> has been processed.</p>';
$body .= '<p style="background:#ecfdf5;border-left:4px solid #10b981;padding:14px;border-radius:6px;font-size:14px;">';
$body .= '<strong>Amount refunded:</strong> ' . kb_money((int) $refund_amount) . '<br>';
$body .= '<strong>Method:</strong> Original payment method<br>';
$body .= '<strong>Timing:</strong> 5-7 business days to reflect (faster for UPI)';
$body .= '</p>';
$body .= '<p style="margin-top:18px;">Thank you for shopping with Khoobie. We\'d love to have you back!</p>';
$body .= '<p><a href="' . base_url('shop') . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Browse shop again →</a></p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Refund processed', 'body' => $body]);
