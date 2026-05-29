<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;">Your order is on the way 🚚</h2>';
$body .= '<p>Order <strong>#' . esc($order_number) . '</strong> shipped via <strong>' . esc($courier) . '</strong>.</p>';
$body .= '<p style="background:#f1f5f9;padding:12px;border-radius:6px;font-family:monospace;">AWB: <strong>' . esc($awb) . '</strong></p>';
if (! empty($tracking_url)) {
    $body .= '<p><a href="' . esc($tracking_url) . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Track shipment</a></p>';
}
$body .= '<p style="font-size:13px;color:#64748b;margin-top:24px;">Typical delivery: ' . esc($estimate ?? '2–6 business days') . '. We\'ll email you again once it\'s delivered.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Shipped', 'body' => $body]);
