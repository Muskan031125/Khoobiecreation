<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;">Your order is confirmed! ✅</h2>';
$body .= '<p>Hi ' . esc($name) . ', great news — your order <strong>#' . esc($order_number) . '</strong> has been confirmed and we\'re packing it up for shipment.</p>';
$body .= '<p>You\'ll get tracking details by email and WhatsApp the moment it\'s on the way.</p>';
$body .= '<p><a href="' . esc($order_url ?? '#') . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">View order</a></p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Order confirmed', 'body' => $body]);
