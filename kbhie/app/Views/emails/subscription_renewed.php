<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">🔄 Subscription renewed</h2>';
$body .= '<p>Hi ' . esc($name) . ', your <strong>' . esc($plan_name) . '</strong> just renewed automatically.</p>';
$body .= '<p style="background:#ecfdf5;border-left:4px solid #10b981;padding:14px;border-radius:6px;font-size:14px;">';
$body .= '<strong>Amount charged:</strong> ' . kb_money((int) $amount) . '<br>';
$body .= '<strong>Order:</strong> #' . esc($order_number) . '<br>';
$body .= '<strong>Next billing:</strong> ' . kb_date($next_billing);
$body .= '</p>';
$body .= '<p style="margin-top:18px;font-size:13px;color:#64748b;">Manage or cancel anytime at <a href="' . base_url('account/subscriptions') . '" style="color:#FF6F61;">My Subscriptions</a>.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Subscription renewed', 'body' => $body]);
