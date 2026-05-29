<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\Payments\RazorpayService;
use CodeIgniter\Controller;
use Config\Database;

class WebhookController extends Controller
{
    public function razorpay()
    {
        $payload = $this->request->getBody();
        $signature = $this->request->getHeaderLine('X-Razorpay-Signature');
        $rzp = new RazorpayService();
        $valid = $rzp->verifyWebhookSignature((string) $payload, $signature);
        $event = json_decode((string) $payload, true) ?: [];
        Database::connect()->table('webhook_log')->insert([
            'source'          => 'razorpay',
            'event'           => $event['event'] ?? 'unknown',
            'reference_id'    => $event['payload']['payment']['entity']['id'] ?? null,
            'headers'         => json_encode(['X-Razorpay-Signature' => $signature]),
            'payload'         => $payload,
            'signature_valid' => $valid ? 1 : 0,
            'processed_at'    => date('Y-m-d H:i:s'),
        ]);
        if (! $valid) return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        try {
            $rzp->handleWebhookEvent($event);
            return $this->response->setJSON(['ok' => true]);
        } catch (\Throwable $e) {
            Database::connect()->table('webhook_log')->where('payload', $payload)->orderBy('id', 'DESC')->limit(1)->update(['success' => 0, 'error' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function phonepe()
    {
        // PhonePe sends server-to-server S2S callback (same shape as PaymentController::phonepeCallback)
        $base64Body = (string) $this->request->getPost('response') ?: (string) $this->request->getBody();
        $xVerify    = $this->request->getHeaderLine('X-VERIFY');
        $pp = new \App\Libraries\Payments\PhonePeService();
        $valid = $pp->verifyCallback($base64Body, $xVerify);
        Database::connect()->table('webhook_log')->insert([
            'source'          => 'phonepe',
            'event'           => 'payment_status',
            'payload'         => $base64Body,
            'signature_valid' => $valid ? 1 : 0,
            'processed_at'    => date('Y-m-d H:i:s'),
        ]);
        if (! $valid) return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        $decoded = json_decode((string) base64_decode($base64Body), true) ?? [];
        $pp->handleCallback($decoded);
        return $this->response->setJSON(['ok' => true]);
    }

    public function shiprocket()
    {
        $payload = $this->request->getBody();
        $event = json_decode((string) $payload, true) ?: [];
        Database::connect()->table('webhook_log')->insert([
            'source'       => 'shiprocket',
            'event'        => $event['current_status'] ?? 'tracking_update',
            'reference_id' => $event['awb'] ?? null,
            'payload'      => $payload,
            'signature_valid' => 1,
            'processed_at' => date('Y-m-d H:i:s'),
        ]);
        if (! empty($event['awb'])) {
            $shipment = Database::connect()->table('shipments')->where('awb', $event['awb'])->get()->getRowArray();
            if ($shipment) {
                Database::connect()->table('shipment_tracking_events')->insert([
                    'shipment_id' => $shipment['id'],
                    'status'      => $event['current_status'] ?? 'update',
                    'description' => $event['shipment_status'] ?? null,
                    'location'    => $event['location'] ?? null,
                    'occurred_at' => $event['updated_time_stamp'] ?? date('Y-m-d H:i:s'),
                    'raw'         => $payload,
                ]);
                if (($event['current_status'] ?? '') === 'DELIVERED') {
                    Database::connect()->table('shipments')->where('id', $shipment['id'])->update([
                        'status' => 'delivered',
                        'delivered_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
        return $this->response->setJSON(['ok' => true]);
    }

    public function whatsapp()
    {
        // Meta WhatsApp Cloud API webhook (incoming messages + delivery receipts)
        $challenge = $this->request->getGet('hub_challenge');
        if ($challenge) return $this->response->setBody($challenge);
        $payload = $this->request->getBody();
        Database::connect()->table('webhook_log')->insert([
            'source'       => 'meta_whatsapp',
            'event'        => 'message_event',
            'payload'      => $payload,
            'signature_valid' => 1,
            'processed_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->response->setJSON(['ok' => true]);
    }
}
