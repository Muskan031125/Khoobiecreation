<?php
$body  = '<h2 style="margin:0 0 12px;font-size:22px;font-family:Fraunces,serif;">🎓 Your trial class is booked!</h2>';
$body .= '<p>Hi ' . esc($name) . ',</p>';
$body .= '<p>Great news — your <strong>free trial class</strong> for <strong>' . esc($product_name) . '</strong> is on the calendar.</p>';
if (! empty($schedule)) {
    $body .= '<p style="background:#eef2ff;border-left:4px solid #6366f1;padding:14px;border-radius:6px;font-size:14px;">'
           . '<strong>📅 Schedule:</strong> ' . esc($schedule) . '<br>'
           . '<strong>👩‍🏫 Instructor:</strong> ' . esc($instructor ?? 'Khoobie-vetted') . '<br>'
           . '<strong>🎥 Format:</strong> Live via Zoom — link will be on WhatsApp 30 min before</p>';
}
$body .= '<p style="margin-top:18px;"><strong>What to bring:</strong></p>';
$body .= '<ul style="font-size:14px;color:#475569;"><li>A quiet room with a desk</li><li>Notebook + pen for your child</li><li>Charged laptop / tablet with good wifi</li></ul>';
$body .= '<p style="margin-top:18px;">No card needed. After the trial, you decide whether to continue. Cancel anytime, no questions.</p>';
$body .= '<p style="margin-top:24px;"><a href="' . esc($product_url ?? '#') . '" style="display:inline-block;padding:12px 24px;background:#FF6F61;color:#fff;border-radius:999px;text-decoration:none;font-weight:bold;">View class details</a></p>';
$body .= '<p style="font-size:13px;color:#64748b;">Questions? Reply to this email or WhatsApp us at +91 88992 23300.</p>';
echo $this->include('emails/_layout', ['subject' => $subject ?? 'Your trial class is booked', 'body' => $body]);
