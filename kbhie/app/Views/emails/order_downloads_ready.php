<?php
$rows = '';
foreach (($links ?? []) as $l) {
    $rows .= '<tr><td style="padding:14px 0;border-bottom:1px solid #f1f5f9;">'
           . '<div style="font-weight:bold;">' . esc($l['product_name']) . '</div>'
           . '<div style="color:#64748b;font-size:13px;">' . esc($l['file_name']) . '</div>'
           . '</td><td style="padding:14px 0;border-bottom:1px solid #f1f5f9;text-align:right;">'
           . '<a href="' . esc($l['url']) . '" style="display:inline-block;padding:10px 16px;background:#FF6F61;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold;font-size:14px;">⬇ Download</a>'
           . '</td></tr>';
}

$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">⚡ Your downloads are ready</h2>';
$body .= '<p>Hi ' . esc($name) . ', here are your instant-download files from order <strong>#' . esc($order_number) . '</strong>.</p>';
$body .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;">' . $rows . '</table>';
$body .= '<p style="margin-top:18px;font-size:13px;color:#64748b;">Each link works for <strong>10 downloads</strong> over <strong>90 days</strong>. Re-access them anytime at <a href="' . base_url('account/downloads') . '" style="color:#FF6F61;">My Downloads</a>.</p>';
$body .= '<p style="font-size:13px;color:#64748b;">Trouble? Reply to this email — we\'ll send fresh links.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Your downloads are ready', 'body' => $body]);
