<?php
$body = '<h2 style="margin:0 0 12px;font-size:22px;">Welcome to the Khoobie family, ' . esc($name) . '! 🎉</h2>'
      . '<p>We\'re so glad you joined us. As a welcome gift, you\'ve got <strong>100 Khoobie Points</strong> already loaded into your account.</p>'
      . '<p><a href="' . esc($shop_url) . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Start shopping &rarr;</a></p>'
      . '<p style="margin-top:24px;">Use code <code style="background:#fff5f3;padding:4px 8px;border-radius:4px;font-weight:bold;">WELCOME10</code> on your first order for an extra 10% off.</p>'
      . '<p>Any questions, just reply to this email or WhatsApp us at +91 88992 23300 — a real human will answer.</p>'
      . '<p>Warmly,<br>The Khoobie Creations team</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Welcome', 'body' => $body]);
