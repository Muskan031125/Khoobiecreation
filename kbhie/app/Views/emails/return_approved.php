<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">✓ Return approved</h2>';
$body .= '<p>Hi ' . esc($name) . ', good news — your return request <strong>' . esc($return_number) . '</strong> for order <strong>#' . esc($order_number) . '</strong> has been approved.</p>';
$body .= '<p style="background:#ecfdf5;border-left:4px solid #10b981;padding:14px;border-radius:6px;font-size:14px;">';
$body .= '<strong>💸 Refund amount:</strong> ' . kb_money((int) $refund_amount) . '<br>';
$body .= '<strong>📦 Next:</strong> Our courier will pick up the items in 2 business days from your shipping address.<br>';
$body .= '<strong>⏱ Refund timing:</strong> 5-7 business days after pickup, back to your original payment method.';
$body .= '</p>';
$body .= '<p style="font-size:13px;color:#64748b;">Questions? Reply to this email or WhatsApp +91 88992 23300.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Return approved', 'body' => $body]);
