<?php

namespace App\Libraries\Payments;

use Config\Database;

/**
 * PhonePe PG integration (Standard checkout).
 * Reads PHONEPE_* env vars. Set env=sandbox for test, env=prod for live.
 */
class PhonePeService
{
    protected string $merchantId;
    protected string $saltKey;
    protected int $saltIndex;
    protected string $env;
    protected $db;

    public function __construct()
    {
        $this->merchantId = (string) env('payments.phonepe.merchant_id', '');
        $this->saltKey    = (string) env('payments.phonepe.salt_key', '');
        $this->saltIndex  = (int) env('payments.phonepe.salt_index', 1);
        $this->env        = (string) env('payments.phonepe.env', 'sandbox');
        $this->db         = Database::connect();
    }

    protected function baseUrl(): string
    {
        return $this->env === 'prod'
            ? 'https://api.phonepe.com/apis/hermes'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    /** Initiate a payment. Returns the PhonePe redirect URL for the customer. */
    public function initiate(int $orderId, int $amountPaise, string $merchantTxnId, string $callbackUrl, string $redirectUrl, ?string $userMobile = null): array
    {
        if (! $this->merchantId || ! $this->saltKey) {
            return ['ok' => false, 'error' => 'PhonePe not configured.'];
        }
        $payload = [
            'merchantId'            => $this->merchantId,
            'merchantTransactionId' => $merchantTxnId,
            'amount'                => $amountPaise,
            'redirectUrl'           => $redirectUrl,
            'redirectMode'          => 'POST',
            'callbackUrl'           => $callbackUrl,
            'mobileNumber'          => $userMobile ? preg_replace('/\D/', '', $userMobile) : null,
            'paymentInstrument'     => ['type' => 'PAY_PAGE'],
        ];
        $base64 = base64_encode(json_encode(array_filter($payload, fn ($v) => $v !== null)));
        $endpoint = '/pg/v1/pay';
        $checksum = hash('sha256', $base64 . $endpoint . $this->saltKey) . '###' . $this->saltIndex;

        $ch = curl_init($this->baseUrl() . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode(['request' => $base64]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-VERIFY: ' . $checksum,
                'accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode((string) $resp, true);

        $this->db->table('payments')->insert([
            'order_id'         => $orderId,
            'gateway'          => 'phonepe',
            'gateway_order_id' => $merchantTxnId,
            'amount'           => $amountPaise,
            'status'           => 'initiated',
            'raw_request'      => json_encode($payload),
            'raw_response'     => json_encode($json),
        ]);

        if ($code >= 400 || empty($json['success'])) {
            return ['ok' => false, 'error' => $json['message'] ?? 'PhonePe init failed'];
        }
        $redirect = $json['data']['instrumentResponse']['redirectInfo']['url'] ?? null;
        return $redirect
            ? ['ok' => true, 'redirect_url' => $redirect, 'merchant_txn_id' => $merchantTxnId]
            : ['ok' => false, 'error' => 'No redirect URL returned'];
    }

    /** Verify callback X-VERIFY header. */
    public function verifyCallback(string $base64Body, string $xVerify): bool
    {
        $expected = hash('sha256', $base64Body . $this->saltKey) . '###' . $this->saltIndex;
        return hash_equals($expected, $xVerify);
    }

    public function handleCallback(array $decodedPayload): void
    {
        $merchantTxn = $decodedPayload['data']['merchantTransactionId'] ?? $decodedPayload['merchantTransactionId'] ?? null;
        if (! $merchantTxn) return;
        $payment = $this->db->table('payments')->where('gateway_order_id', $merchantTxn)->get()->getRowArray();
        if (! $payment) return;

        $code = $decodedPayload['code'] ?? 'PAYMENT_PENDING';
        $status = match ($code) {
            'PAYMENT_SUCCESS' => 'captured',
            'PAYMENT_ERROR', 'PAYMENT_DECLINED' => 'failed',
            'PAYMENT_CANCELLED' => 'cancelled',
            default => 'pending',
        };
        $this->db->table('payments')->where('id', $payment['id'])->update([
            'status'             => $status,
            'gateway_payment_id' => $decodedPayload['data']['transactionId'] ?? null,
            'paid_at'            => $status === 'captured' ? date('Y-m-d H:i:s') : null,
            'raw_response'       => json_encode($decodedPayload),
        ]);
        if ($status === 'captured') {
            (new RazorpayService())->updateOrderAfterPayment((int) $payment['order_id']);
        }
    }

    /** Server-to-server status check used by callbacks for double-verification. */
    public function checkStatus(string $merchantTxnId): ?array
    {
        if (! $this->merchantId) return null;
        $endpoint = "/pg/v1/status/{$this->merchantId}/{$merchantTxnId}";
        $checksum = hash('sha256', $endpoint . $this->saltKey) . '###' . $this->saltIndex;
        $ch = curl_init($this->baseUrl() . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'X-VERIFY: ' . $checksum,
                'X-MERCHANT-ID: ' . $this->merchantId,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode((string) $resp, true);
    }
}
