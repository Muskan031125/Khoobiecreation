<?php

namespace App\Libraries\Payments;

use Config\Database;

/**
 * Razorpay Orders + Payment verification + Webhook handling.
 * Uses direct API calls (no SDK) for minimal dependencies.
 * Set RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET, RAZORPAY_WEBHOOK_SECRET in .env.
 */
class RazorpayService
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;
    protected $db;

    public function __construct()
    {
        $this->keyId         = (string) env('payments.razorpay.key_id', '');
        $this->keySecret     = (string) env('payments.razorpay.key_secret', '');
        $this->webhookSecret = (string) env('payments.razorpay.webhook_secret', '');
        $this->db            = Database::connect();
    }

    /** Create a Razorpay order; persist to payments table. */
    public function createOrder(int $orderId, int $amountPaise, string $receipt): array
    {
        if (! $this->keyId || ! $this->keySecret) {
            return ['ok' => false, 'error' => 'Razorpay not configured. Add keys to .env.'];
        }

        $body = [
            'amount'   => $amountPaise,
            'currency' => 'INR',
            'receipt'  => $receipt,
            'notes'    => ['order_id' => (string) $orderId],
        ];
        $resp = $this->call('POST', '/v1/orders', $body);
        if (! $resp['ok']) return $resp;

        $rzpOrder = $resp['body'];
        $this->db->table('payments')->insert([
            'order_id'         => $orderId,
            'gateway'          => 'razorpay',
            'gateway_order_id' => $rzpOrder['id'],
            'amount'           => $amountPaise,
            'status'           => 'initiated',
            'raw_response'     => json_encode($rzpOrder),
        ]);

        return [
            'ok'        => true,
            'order_id'  => $rzpOrder['id'],
            'amount'    => $rzpOrder['amount'],
            'currency'  => $rzpOrder['currency'],
            'key_id'    => $this->keyId,
            'payment_id'=> (int) $this->db->insertID(),
        ];
    }

    /** Verify signature posted by checkout.js after success. */
    public function verifyCheckoutSignature(string $razorpayOrderId, string $razorpayPaymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret);
        return hash_equals($expected, $signature);
    }

    public function recordSuccessfulPayment(int $orderId, string $rzpOrderId, string $rzpPaymentId, array $raw = []): void
    {
        $payment = $this->db->table('payments')
            ->where('order_id', $orderId)
            ->where('gateway_order_id', $rzpOrderId)
            ->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
        if ($payment) {
            $this->db->table('payments')->where('id', $payment['id'])->update([
                'gateway_payment_id' => $rzpPaymentId,
                'status'             => 'captured',
                'paid_at'            => date('Y-m-d H:i:s'),
                'raw_response'       => json_encode(array_merge(json_decode($payment['raw_response'] ?? '{}', true) ?: [], $raw)),
            ]);
        }
        $this->updateOrderAfterPayment($orderId);
    }

    /** Validate webhook signature using Razorpay's HMAC SHA256. */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (! $this->webhookSecret) return false;
        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expected, $signature);
    }

    public function handleWebhookEvent(array $event): void
    {
        $type = $event['event'] ?? '';
        if ($type === 'payment.captured') {
            $pe = $event['payload']['payment']['entity'] ?? null;
            if ($pe) {
                $orderNotes = $pe['notes'] ?? [];
                $orderId = isset($orderNotes['order_id']) ? (int) $orderNotes['order_id'] : null;
                if ($orderId) {
                    $this->recordSuccessfulPayment($orderId, $pe['order_id'], $pe['id'], ['webhook' => $pe]);
                }
            }
        }
        if ($type === 'payment.failed') {
            $pe = $event['payload']['payment']['entity'] ?? null;
            if ($pe) {
                $this->db->table('payments')->where('gateway_payment_id', $pe['id'])->update([
                    'status'         => 'failed',
                    'error_code'     => $pe['error_code']        ?? null,
                    'error_message'  => $pe['error_description'] ?? null,
                ]);
            }
        }
        if ($type === 'refund.processed') {
            $re = $event['payload']['refund']['entity'] ?? null;
            if ($re) {
                $this->db->table('payment_refunds')->where('gateway_refund_id', $re['id'])->update([
                    'status'       => 'processed',
                    'refunded_at'  => date('Y-m-d H:i:s'),
                    'raw_response' => json_encode($re),
                ]);
            }
        }
    }

    public function updateOrderAfterPayment(int $orderId): void
    {
        $sum = (int) ($this->db->table('payments')
            ->selectSum('amount', 's')
            ->where('order_id', $orderId)
            ->where('status', 'captured')
            ->get()->getRow()->s ?? 0);
        $order = $this->db->table('orders')->where('id', $orderId)->get()->getRowArray();
        if (! $order) return;

        $update = ['amount_paid' => $sum, 'amount_due' => max(0, (int) $order['grand_total'] - $sum)];
        if ($sum >= (int) $order['grand_total']) {
            $update['status']       = 'processing';
            $update['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($order['payment_method'] === 'partial_cod' && $sum > 0) {
            $update['status'] = 'pending_confirmation';
        }
        $this->db->table('orders')->where('id', $orderId)->update($update);
        $this->db->table('order_status_history')->insert([
            'order_id'  => $orderId,
            'from_status' => $order['status'],
            'to_status'   => $update['status'] ?? $order['status'],
            'channel'     => 'webhook',
            'note'        => 'Payment captured via Razorpay',
        ]);
    }

    protected function call(string $method, string $path, array $body = []): array
    {
        $ch = curl_init('https://api.razorpay.com' . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->keyId . ':' . $this->keySecret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);
        if ($method !== 'GET') curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) return ['ok' => false, 'error' => 'Network error: ' . $err];
        $json = json_decode((string) $resp, true);
        if ($code >= 400) return ['ok' => false, 'error' => $json['error']['description'] ?? 'Razorpay error', 'code' => $code];
        return ['ok' => true, 'body' => $json, 'code' => $code];
    }
}
