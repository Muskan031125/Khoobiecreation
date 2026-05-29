<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;">You\'re in! 🎁</h2>';
$body .= '<p>Thanks for joining our newsletter. As promised, here\'s your code for <strong>10% off</strong> your first order:</p>';
$body .= '<div style="font-size:32px;letter-spacing:6px;font-weight:900;text-align:center;background:#fff5f3;color:#E94B3C;padding:20px;border-radius:12px;margin:24px 0;font-family:monospace;">WELCOME10</div>';
$body .= '<p><a href="' . esc($shop_url) . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Start shopping &rarr;</a></p>';
$body .= '<p style="font-size:13px;color:#64748b;margin-top:24px;">We send 1–2 emails a month — new launches, behind-the-scenes peeks, and the occasional surprise. Unsubscribe any time.</p>';
echo $this->include('emails/_layout', ['subject' => 'Welcome to Khoobie', 'body' => $body]);
