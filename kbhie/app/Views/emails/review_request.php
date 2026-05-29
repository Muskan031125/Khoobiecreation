<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;">How did we do?</h2>';
$body .= '<p>Hi ' . esc($name) . ', we hope ' . esc($child_name ?? 'your child') . ' is enjoying <strong>' . esc($product_name) . '</strong>.</p>';
$body .= '<p>If you\'ve got a minute, would you share a quick review? Every review helps another family discover screen-free joy — and you earn <strong>50 Khoobie Points</strong> for sharing one.</p>';
$body .= '<p><a href="' . esc($review_url) . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Write a review</a></p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'How did we do?', 'body' => $body]);
