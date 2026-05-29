<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">🎉 New order to fulfill</h2>';
$body .= '<p>Hi ' . esc($partner_name) . ',</p>';
$body .= '<p>You have a new order on Khoobie — <strong>#' . esc($order_number) . '</strong>.</p>';

$body .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;background:#f8fafc;border-radius:8px;">';
$body .= '<tr><td style="padding:14px;">';
$body .= '<div style="font-weight:bold;font-size:14px;">📦 Items to ship</div>';
$body .= '<pre style="margin:8px 0;font-family:monospace;font-size:13px;color:#475569;white-space:pre-wrap;">' . esc($items_text) . '</pre>';
$body .= '</td></tr></table>';

$body .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:14px;background:#ecfdf5;border-radius:8px;">';
$body .= '<tr><td style="padding:14px;">';
$body .= '<div style="font-weight:bold;font-size:14px;">🏠 Ship to</div>';
$body .= '<div style="font-size:13px;color:#475569;"><strong>' . esc($customer_name) . '</strong><br>';
$ship = json_decode($shipping ?? '{}', true) ?: [];
$body .= esc(($ship['line1'] ?? '') . (! empty($ship['line2']) ? ', ' . $ship['line2'] : '')) . '<br>';
$body .= esc(($ship['city'] ?? '') . ', ' . ($ship['state'] ?? '') . ' — ' . ($ship['pincode'] ?? ''));
$body .= '</div>';
$body .= '</td></tr></table>';

$body .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:14px;background:#fff7ed;border-radius:8px;">';
$body .= '<tr><td style="padding:14px;font-size:14px;">';
$body .= '<strong>💰 Your payout:</strong> ₹' . number_format(round($payout/100)) . ' <span style="color:#64748b;">(after Khoobie commission ₹' . number_format(round($commission/100)) . ')</span>';
$body .= '</td></tr></table>';

$body .= '<p style="margin-top:24px;"><a href="' . esc($portal_url) . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">Open Partner Portal →</a></p>';
$body .= '<p style="font-size:13px;color:#64748b;"><strong>Next:</strong> Pack & dispatch within 24h. Upload tracking number in the portal. Get paid weekly on Fridays.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'New order to fulfill', 'body' => $body]);
