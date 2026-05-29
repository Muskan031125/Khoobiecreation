<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;">Refund processed</h2>';
$body .= '<p>Hi ' . esc($name) . ', we\'ve refunded ' . kb_money((int) $amount) . ' for order <strong>#' . esc($order_number) . '</strong>.</p>';
$body .= '<p>It should reflect in your account in 3–7 business days, depending on your bank. If you don\'t see it after 7 days, reply to this email and we\'ll chase it for you.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Refund processed', 'body' => $body]);
