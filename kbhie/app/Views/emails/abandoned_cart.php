<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">👋 You left these behind</h2>';
$body .= '<p>' . esc($message ?? 'Your Khoobie cart is waiting for you.') . '</p>';
$body .= '<p style="background:#fff7ed;border-left:4px solid #f97316;padding:14px;border-radius:6px;font-size:14px;">'
       . '🎁 <strong>Code WELCOME10</strong> takes <strong>10% off</strong> your first order — auto-applies at checkout. Valid for 7 days.'
       . '</p>';
$body .= '<p style="margin-top:24px;"><a href="' . esc($cart_url) . '" style="display:inline-block;padding:14px 28px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;font-size:16px;">Resume my cart →</a></p>';
$body .= '<p style="font-size:13px;color:#64748b;margin-top:24px;">Don\'t want emails about your cart? Just reply STOP and we won\'t bother you again.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Your Khoobie cart', 'body' => $body]);
