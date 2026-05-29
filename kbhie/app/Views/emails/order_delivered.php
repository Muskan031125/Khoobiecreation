<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;">Delivered! 🎉</h2>';
$body .= '<p>Hi ' . esc($name) . ', your order <strong>#' . esc($order_number) . '</strong> was delivered today. We hope ' . esc($child_name ?? 'your little one') . ' loves it.</p>';
$body .= '<p>If anything\'s missing or not quite right, just hit reply within 7 days — we\'ll make it right, no questions asked.</p>';
$body .= '<p><a href="' . esc($review_url ?? '#') . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Leave a review (earn 50 Khoobie Points)</a></p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Order delivered', 'body' => $body]);
