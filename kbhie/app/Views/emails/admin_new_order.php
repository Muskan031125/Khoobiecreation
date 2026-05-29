<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;">New order: #' . esc($order_number) . '</h2>';
$body .= '<p><strong>Customer:</strong> ' . esc($name) . ' · ' . esc($phone) . ' · ' . esc($email) . '</p>';
$body .= '<p><strong>Total:</strong> ' . kb_money((int) $amount) . ' (' . esc($payment_method) . ')</p>';
$body .= '<p><strong>Placed:</strong> ' . kb_date($placed_at, true) . '</p>';
if (! empty($needs_confirmation)) {
    $body .= '<p style="background:#fffbeb;border-left:3px solid #f59e0b;padding:12px;border-radius:6px;"><strong>Action:</strong> COD order — call customer to confirm before shipping.</p>';
}
$body .= '<p><a href="' . esc($admin_url) . '" style="display:inline-block;padding:12px 24px;background:#0f172a;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Open in admin</a></p>';
echo $this->include('emails/_layout', ['subject' => 'New order — ' . esc($order_number), 'body' => $body]);
