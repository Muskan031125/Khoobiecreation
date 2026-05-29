<?php

namespace App\Libraries;

use Config\Database;
use Config\Services;

/**
 * Unified intent capture.
 * One service handles every non-cart conversion flow across the platform:
 *   trial · rsvp · reserve_seat · discovery_call · notify_me · contact_instructor · enquire
 *
 * Two-step verify: capture → OTP send → OTP verify → (optional) part-payment → reserved.
 */
class IntentService
{
    private const OTP_VALID_MIN = 10;

    private $db;
    public function __construct() { $this->db = Database::connect(); }

    /**
     * Step 1 — capture intent + send OTP.
     * Returns ['ok'=>true, 'intent_id'=>N, 'masked_phone'=>'+91 XXXXX X5678']
     * Or    ['ok'=>false, 'errors'=>[...]] on validation failure.
     */
    public function capture(array $input): array
    {
        $rules = [
            'product_id' => 'required|integer',
            'kind'       => 'required|in_list[trial,rsvp,reserve_seat,discovery_call,notify_me,contact_instructor,enquire]',
            'name'       => 'permit_empty|min_length[2]|max_length[150]',
            'phone'      => 'permit_empty|regex_match[/^\+?\d{10,15}$/]',
            'email'      => 'permit_empty|valid_email',
        ];

        $errors = [];
        if (! \Config\Services::validation()->setRules($rules)->run($input)) {
            $errors = \Config\Services::validation()->getErrors();
        }
        // We need at least ONE channel — phone OR email
        if (empty($input['phone']) && empty($input['email'])) {
            $errors['contact'] = 'Either phone or email is required so we can confirm.';
        }
        if ($errors) return ['ok' => false, 'errors' => $errors];

        $product = $this->db->table('products')->where('id', (int) $input['product_id'])->get()->getRow();
        if (! $product) return ['ok' => false, 'errors' => ['product' => 'Product not found.']];

        $otp        = (string) random_int(100000, 999999);
        $request    = Services::request();
        $anonId     = $request->getCookie('kb_anon') ?: ('sess_' . session_id());
        $user       = session('user');

        // Compute part-payment baseline if this is a seat reservation
        $amountDue  = (int) ($input['amount_due'] ?? 0);

        $payload = [
            'product_id'       => $product->id,
            'product_type'     => $product->type,
            'kind'             => $input['kind'],
            'name'             => $input['name']         ?? null,
            'email'            => $input['email']        ?? null,
            'phone'            => $input['phone']        ?? null,
            'child_name'       => $input['child_name']   ?? null,
            'child_age'        => isset($input['child_age']) ? (int) $input['child_age'] : null,
            'preferred_slot'   => $input['preferred_slot'] ?? null,
            'message'          => $input['message']      ?? null,
            'otp'              => $otp,
            'otp_channel'      => ! empty($input['phone']) ? 'whatsapp' : 'email',
            'otp_sent_at'      => date('Y-m-d H:i:s'),
            'amount_due'       => $amountDue ?: null,
            'amount_paid'      => 0,
            'status'           => 'pending',
            'anon_id'          => $anonId,
            'user_id'          => $user['id'] ?? null,
            'attribution_code' => 'KHOOBIE',
            'ip'               => $request->getIPAddress(),
            'user_agent'       => substr((string) $request->getUserAgent(), 0, 500),
            'metadata'         => json_encode($input['metadata'] ?? []),
            'attribution'      => json_encode(session('attribution') ?: []),
        ];

        $this->db->table('intents')->insert($payload);
        $intentId = (int) $this->db->insertID();

        // Dispatch OTP — re-uses existing OTP infrastructure if available, else just logs
        $this->dispatchOtp($intentId, $payload);

        return [
            'ok'          => true,
            'intent_id'   => $intentId,
            'masked'      => $this->mask($payload['phone'] ?: $payload['email']),
            'channel'     => $payload['otp_channel'],
            'requires_payment' => $amountDue > 0,
            'amount_due'  => $amountDue,
        ];
    }

    /** Step 2 — verify OTP. Returns ['ok'=>true, 'intent_id'=>N, 'requires_payment'=>bool] */
    public function verifyOtp(int $intentId, string $code): array
    {
        $row = $this->db->table('intents')->where('id', $intentId)->get()->getRow();
        if (! $row) return ['ok' => false, 'error' => 'Intent not found.'];
        if ($row->verified_at) return ['ok' => true, 'intent_id' => $intentId, 'requires_payment' => (int) $row->amount_due > 0, 'already' => true];

        if (strtotime($row->otp_sent_at) < strtotime('-' . self::OTP_VALID_MIN . ' minutes')) {
            return ['ok' => false, 'error' => 'OTP expired. Please request a new one.'];
        }
        if (trim($code) !== (string) $row->otp) {
            return ['ok' => false, 'error' => 'Wrong OTP. Try again.'];
        }

        $newStatus = ((int) $row->amount_due > 0) ? 'pending' : ($row->kind === 'reserve_seat' ? 'pending' : 'verified');
        // Free flows (trial, RSVP, notify_me, discovery_call) → mark verified now.
        // Paid flows (reserve_seat with amount_due) → stay pending until part-payment captured.
        $this->db->table('intents')->where('id', $intentId)->update([
            'verified_at' => date('Y-m-d H:i:s'),
            'status'      => $newStatus,
        ]);

        return [
            'ok'               => true,
            'intent_id'        => $intentId,
            'requires_payment' => (int) $row->amount_due > 0,
            'amount_due'       => (int) $row->amount_due,
        ];
    }

    /** Resend OTP — rate-limited by sent_at. */
    public function resendOtp(int $intentId): array
    {
        $row = $this->db->table('intents')->where('id', $intentId)->get()->getRow();
        if (! $row) return ['ok' => false, 'error' => 'Intent not found.'];
        if (strtotime($row->otp_sent_at) > strtotime('-30 seconds')) {
            return ['ok' => false, 'error' => 'Please wait 30 seconds before requesting another OTP.'];
        }
        $otp = (string) random_int(100000, 999999);
        $this->db->table('intents')->where('id', $intentId)->update([
            'otp' => $otp, 'otp_sent_at' => date('Y-m-d H:i:s'),
        ]);
        $this->dispatchOtp($intentId, (array) $row);
        return ['ok' => true, 'masked' => $this->mask($row->phone ?: $row->email)];
    }

    /** Step 3 (optional) — capture part-payment. In production wires to Razorpay/PhonePe. */
    public function capturePartPayment(int $intentId, int $amountPaid, string $gatewayRef, string $gateway = 'razorpay'): array
    {
        $row = $this->db->table('intents')->where('id', $intentId)->get()->getRow();
        if (! $row) return ['ok' => false, 'error' => 'Intent not found.'];
        if (! $row->verified_at) return ['ok' => false, 'error' => 'Verify OTP first.'];

        $this->db->table('intents')->where('id', $intentId)->update([
            'amount_paid'     => $amountPaid,
            'payment_gateway' => $gateway,
            'gateway_ref'     => $gatewayRef,
            'status'          => 'reserved',
        ]);
        return ['ok' => true, 'intent_id' => $intentId, 'status' => 'reserved'];
    }

    /** Stub OTP dispatcher — drop in WhatsApp / SMS / Email gateway here. */
    private function dispatchOtp(int $intentId, array $payload): void
    {
        // Log to file so demo flow still works; replace with actual sender in production.
        $msg = "[INTENT OTP] id={$intentId} otp={$payload['otp']} via={$payload['otp_channel']} to=" . ($payload['phone'] ?? $payload['email']);
        log_message('info', $msg);
    }

    /** Masks "+919876543210" → "+91 XXXXX X3210" and "user@x.com" → "u••r@x.com". */
    private function mask(?string $s): string
    {
        if (! $s) return '';
        if (str_contains($s, '@')) {
            [$u, $d] = explode('@', $s, 2);
            return substr($u, 0, 1) . str_repeat('•', max(1, strlen($u) - 2)) . substr($u, -1) . '@' . $d;
        }
        $tail = substr($s, -4);
        return '+91 XXXXX X' . $tail;
    }
}
