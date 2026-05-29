<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">Return request update</h2>';
$body .= '<p>Hi ' . esc($name) . ', we reviewed return request <strong>' . esc($return_number) . '</strong> and unfortunately could not approve it this time.</p>';
$body .= '<p style="background:#fef3c7;border-left:4px solid #d97706;padding:14px;border-radius:6px;font-size:14px;">';
$body .= '<strong>Reason:</strong> ' . esc($reason ?? 'See order notes');
$body .= '</p>';
$body .= '<p style="font-size:14px;">If you think this is a mistake or want to discuss, just reply — we read every message.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Return request update', 'body' => $body]);
