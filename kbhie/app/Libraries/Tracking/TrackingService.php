<?php

namespace App\Libraries\Tracking;

use Config\Database;
use Config\Services;

/**
 * Server-side pixel mirror. Every client-side event we fire also lands here,
 * gets stored in tracking_events, then dispatched to Meta Conversions API
 * and GA4 Measurement Protocol so iOS / ad-blockers don't blind us.
 */
class TrackingService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function captureEvent(array $payload): int
    {
        $now = date('Y-m-d H:i:s');
        $eventId = $payload['event_id'] ?? bin2hex(random_bytes(16));

        // dedup on event_id
        $existing = $this->db->table('tracking_events')->where('event_id', $eventId)->get()->getRowArray();
        if ($existing) return (int) $existing['id'];

        $user = session('user');
        $row = [
            'event_id'      => $eventId,
            'event_name'    => $payload['event_name'] ?? 'CustomEvent',
            'anon_id'       => $payload['anon_id'] ?? null,
            'user_id'       => $user['id'] ?? null,
            'value'         => $payload['value'] ?? null,
            'currency'      => $payload['currency'] ?? 'INR',
            'url'           => $payload['url'] ?? null,
            'referrer'      => $payload['referrer'] ?? null,
            'ip'            => Services::request()->getIPAddress(),
            'user_agent'    => substr((string) Services::request()->getUserAgent(), 0, 500),
            'fbp'           => $this->cookie('_fbp'),
            'fbc'           => $this->cookie('_fbc'),
            'ga_client_id'  => $payload['ga_client_id'] ?? null,
            'payload'       => json_encode($payload),
            'source'        => $payload['source'] ?? 'client',
            'created_at'    => $now,
        ];
        $this->db->table('tracking_events')->insert($row);
        $id = (int) $this->db->insertID();

        // Fire-and-forget dispatch to Meta CAPI + GA4 — in production this should go to a queue
        try {
            $this->dispatchMeta($id, $row, $payload);
        } catch (\Throwable $e) {
            log_message('error', 'Meta CAPI dispatch failed: ' . $e->getMessage());
        }
        try {
            $this->dispatchGa4($id, $row, $payload);
        } catch (\Throwable $e) {
            log_message('error', 'GA4 dispatch failed: ' . $e->getMessage());
        }
        return $id;
    }

    protected function dispatchMeta(int $id, array $row, array $payload): void
    {
        $pixelId = env('tracking.meta_pixel_id');
        $token   = env('tracking.meta_access_token');
        if (! $pixelId || ! $token) return;

        $user_data = array_filter([
            'em'              => isset($payload['email']) ? [hash('sha256', strtolower(trim($payload['email'])))] : null,
            'ph'              => isset($payload['phone']) ? [hash('sha256', preg_replace('/\D/', '', $payload['phone']))] : null,
            'fbp'             => $row['fbp'],
            'fbc'             => $row['fbc'],
            'client_ip_address'=> $row['ip'],
            'client_user_agent'=> $row['user_agent'],
        ]);

        $body = [
            'data' => [[
                'event_name'       => $row['event_name'],
                'event_time'       => time(),
                'event_id'         => $row['event_id'],
                'event_source_url' => $row['url'],
                'action_source'    => 'website',
                'user_data'        => $user_data,
                'custom_data'      => array_filter([
                    'value'    => $row['value'] !== null ? $row['value'] / 100 : null,
                    'currency' => $row['currency'],
                ] + ($payload['custom_data'] ?? [])),
            ]],
        ];
        if (env('tracking.meta_test_event_code')) {
            $body['test_event_code'] = env('tracking.meta_test_event_code');
        }

        $url = "https://graph.facebook.com/v18.0/{$pixelId}/events?access_token={$token}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->db->table('tracking_events')->where('id', $id)->update([
            'sent_to_meta_at' => date('Y-m-d H:i:s'),
            'meta_response'   => json_encode(['code' => $code, 'body' => json_decode((string) $resp, true)]),
        ]);
    }

    protected function dispatchGa4(int $id, array $row, array $payload): void
    {
        $mid    = env('tracking.ga4_measurement_id');
        $secret = env('tracking.ga4_api_secret');
        if (! $mid || ! $secret) return;

        $clientId = $row['ga_client_id'] ?? ($row['anon_id'] ?: bin2hex(random_bytes(8)));
        $body = [
            'client_id' => $clientId,
            'events'    => [[
                'name'   => $this->ga4Name($row['event_name']),
                'params' => array_filter([
                    'value'    => $row['value'] !== null ? $row['value'] / 100 : null,
                    'currency' => $row['currency'],
                    'page_location' => $row['url'],
                    'event_id'      => $row['event_id'],
                ] + ($payload['ga_params'] ?? [])),
            ]],
        ];
        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$mid}&api_secret={$secret}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->db->table('tracking_events')->where('id', $id)->update([
            'sent_to_ga_at' => date('Y-m-d H:i:s'),
            'ga_response'   => json_encode(['code' => $code]),
        ]);
    }

    protected function ga4Name(string $metaEvent): string
    {
        // Map Meta event names to GA4 recommended names
        return match ($metaEvent) {
            'PageView'         => 'page_view',
            'ViewContent'      => 'view_item',
            'AddToCart'        => 'add_to_cart',
            'InitiateCheckout' => 'begin_checkout',
            'AddPaymentInfo'   => 'add_payment_info',
            'Purchase'         => 'purchase',
            'Lead'             => 'generate_lead',
            'CompleteRegistration' => 'sign_up',
            'Search'           => 'search',
            default            => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $metaEvent)),
        };
    }

    protected function cookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }
}
