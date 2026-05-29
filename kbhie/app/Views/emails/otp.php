<?php
$body = '<h2 style="margin:0 0 12px;font-size:22px;">Your verification code</h2>'
      . '<p>Use the code below to ' . esc($purpose ?? 'sign in') . '. It expires in 10 minutes.</p>'
      . '<div style="font-size:42px;letter-spacing:10px;font-weight:900;text-align:center;background:#fff5f3;color:#E94B3C;padding:20px;border-radius:12px;margin:24px 0;">'
      . esc($code) . '</div>'
      . '<p style="font-size:13px;color:#64748b;">If you didn\'t request this, you can safely ignore this email — nobody can sign in without the code.</p>';
echo $this->include('emails/_layout', ['subject' => 'Your Khoobie verification code', 'body' => $body]);
