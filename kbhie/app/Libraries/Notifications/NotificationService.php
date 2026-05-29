<?php

namespace App\Libraries\Notifications;

use CodeIgniter\Email\Email;
use Config\Database;
use Config\Services;

/**
 * Outbound notification dispatcher. Logs every send to notifications_log.
 * Channels: email (SMTP), whatsapp (Meta Cloud API), sms (MSG91 / generic).
 *
 * Templates are looked up via key — defaults are inlined here; production
 * should move them to db rows or files.
 */
class NotificationService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function send(string $channel, string $recipient, string $templateKey, array $payload = [], ?int $userId = null, ?string $refType = null, ?int $refId = null): int
    {
        $logId = (int) $this->db->table('notifications_log')->insert([
            'channel'      => $channel,
            'recipient'    => $recipient,
            'user_id'      => $userId,
            'template_key' => $templateKey,
            'payload'      => json_encode($payload),
            'ref_type'     => $refType,
            'ref_id'       => $refId,
            'status'       => 'queued',
        ], true);

        try {
            $rendered = $this->renderTemplate($templateKey, $payload);
            $this->db->table('notifications_log')->where('id', $logId)->update(['subject' => $rendered['subject'] ?? null]);

            $ok = match ($channel) {
                'email'    => $this->sendEmail($recipient, $rendered),
                'whatsapp' => $this->sendWhatsApp($recipient, $rendered),
                'sms'      => $this->sendSms($recipient, $rendered),
                default    => false,
            };
            $this->db->table('notifications_log')->where('id', $logId)->update([
                'status'  => $ok ? 'sent' : 'failed',
                'sent_at' => $ok ? date('Y-m-d H:i:s') : null,
                'error'   => $ok ? null : 'Provider not configured or send failed',
            ]);
        } catch (\Throwable $e) {
            $this->db->table('notifications_log')->where('id', $logId)->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
            log_message('error', "Notification {$channel}/{$templateKey} failed: " . $e->getMessage());
        }
        return $logId;
    }

    protected function renderTemplate(string $key, array $p): array
    {
        $brand = env('khoobie.brand_name', 'Khoobie Creations');

        // Map template key → view file + subject + auto-fill defaults
        $map = [
            'order.placed'          => ['view' => 'emails/order_placed',         'subject' => "[{$brand}] Order #{$p['order_number']} received"],
            'order.confirmed'       => ['view' => 'emails/order_confirmed',      'subject' => "[{$brand}] Order #{$p['order_number']} confirmed — shipping soon"],
            'order.shipped'         => ['view' => 'emails/order_shipped',        'subject' => "[{$brand}] Your order is on the way 🚚"],
            'order.delivered'       => ['view' => 'emails/order_delivered',      'subject' => "[{$brand}] Delivered — hope you love it"],
            'refund.processed'      => ['view' => 'emails/refund_processed',     'subject' => "[{$brand}] Refund processed"],
            'review.request'        => ['view' => 'emails/review_request',       'subject' => "[{$brand}] How did we do?"],
            'newsletter.subscribed' => ['view' => 'emails/newsletter_subscribed','subject' => "[{$brand}] Welcome to the Khoobie family"],
            'welcome'               => ['view' => 'emails/welcome',              'subject' => "[{$brand}] Welcome to Khoobie Creations"],
            'otp'                   => ['view' => 'emails/otp',                  'subject' => "[{$brand}] Your verification code"],
            'password.reset'        => ['view' => 'emails/password_reset',       'subject' => "[{$brand}] Reset your password"],
            'cart.abandoned'        => ['view' => 'emails/abandoned_cart',       'subject' => "[{$brand}] You left something behind"],
            'admin.new_order'       => ['view' => 'emails/admin_new_order',      'subject' => "[{$brand}] New order — #{$p['order_number']}"],
            'digital.delivered'     => ['view' => 'emails/order_placed',         'subject' => "[{$brand}] Your download is ready"],
            'enrol.confirmation'    => ['view' => 'emails/enrol_confirmation',   'subject' => "[{$brand}] You're in! Enrolment #{$p['order_number']}"],
        ];

        if (! isset($map[$key])) {
            // Generic fallback — let the caller supply subject + body
            return [
                'subject' => $p['subject'] ?? "[{$brand}] Update",
                'text'    => $p['text']    ?? '',
                'html'    => $p['html']    ?? ($p['text'] ?? ''),
            ];
        }

        $template = $map[$key];
        $vars = array_merge([
            'subject'   => $template['subject'],
            'brand_url' => base_url('/'),
            'shop_url'  => base_url('shop'),
        ], $p);

        $html = view($template['view'], $vars);
        $text = trim(strip_tags(preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html)));

        return [
            'subject' => $template['subject'],
            'html'    => $html,
            'text'    => $text,
        ];
    }

    protected function sendEmail(string $to, array $msg): bool
    {
        $host = env('mail.smtp_host');
        if (! $host) return false;
        $email = Services::email();
        $email->setNewline("\r\n");
        $email->setMailType('html');
        $email->setFrom(env('mail.from_email', 'noreply@khoobie.com'), env('mail.from_name', 'Krafty Khoobie'));
        $email->setTo($to);
        $email->setSubject($msg['subject'] ?? 'Notification');
        $email->setMessage($msg['html'] ?? ($msg['text'] ?? ''));
        if (! $email->send(false)) {
            log_message('error', 'Email send failed: ' . $email->printDebugger(['headers']));
            return false;
        }
        return true;
    }

    protected function sendWhatsApp(string $to, array $msg): bool
    {
        $token  = env('whatsapp.access_token');
        $phoneId = env('whatsapp.phone_number_id');
        if (! $token || ! $phoneId) return false;

        $to = preg_replace('/\D/', '', $to);
        $body = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $msg['text'] ?? $msg['subject']],
        ];
        $ch = curl_init("https://graph.facebook.com/v18.0/{$phoneId}/messages");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code < 400;
    }

    protected function sendSms(string $to, array $msg): bool
    {
        $provider = env('sms.provider', 'msg91');
        if ($provider === 'msg91') {
            $authKey = env('sms.msg91.auth_key');
            $sender  = env('sms.msg91.sender_id');
            if (! $authKey) return false;

            $to = preg_replace('/\D/', '', $to);
            // Using transactional send-otp / send-sms style endpoint
            $body = [
                'mobile'  => $to,
                'message' => $msg['text'] ?? $msg['subject'],
                'sender'  => $sender,
                'route'   => '4',
            ];
            $ch = curl_init('https://api.msg91.com/api/sendhttp.php?' . http_build_query($body) . '&authkey=' . $authKey);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $code < 400;
        }
        return false;
    }
}
