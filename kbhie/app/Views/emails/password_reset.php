<?php
$body = '<h2 style="margin:0 0 12px;font-size:22px;">Reset your password</h2>'
      . '<p>Hi ' . esc($name ?? 'there') . ',</p>'
      . '<p>We received a request to reset the password on your Khoobie Creations account. Click below to set a new one — the link expires in 1 hour.</p>'
      . '<p><a href="' . esc($reset_url) . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Reset my password</a></p>'
      . '<p style="font-size:13px;color:#64748b;margin-top:24px;">If you didn\'t request this, no action is needed — your password stays the same.</p>';
echo $this->include('emails/_layout', ['subject' => 'Reset your Khoobie password', 'body' => $body]);
