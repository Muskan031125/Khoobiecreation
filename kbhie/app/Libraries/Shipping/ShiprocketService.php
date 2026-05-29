<?php

namespace App\Libraries\Shipping;

use Config\Database;

/**
 * Minimal Shiprocket Open API client — create order, generate AWB, request pickup.
 * Auth via email/password to get a 24h token (cached in settings table).
 */
class ShiprocketService
{
    protected string $email;
    protected string $password;
    protected ?int $channelId;
    protected $db;
    protected string $base = 'https://apiv2.shiprocket.in/v1/external';

    public function __construct()
    {
        $this->email     = (string) env('shipping.shiprocket.email', '');
        $this->password  = (string) env('shipping.shiprocket.password', '');
        $this->channelId = env('shipping.shiprocket.channel_id') ? (int) env('shipping.shiprocket.channel_id') : null;
        $this->db        = Database::connect();
    }

    protected function token(): ?string
    {
        if (! $this->email || ! $this->password) return null;
        $cached = $this->db->table('settings')->where('group_key', '_shiprocket')->where('key', 'token')->get()->getRowArray();
        $expires = $this->db->table('settings')->where('group_key', '_shiprocket')->where('key', 'expires_at')->get()->getRowArray();
        if ($cached && $expires && strtotime($expires['value']) > time() + 60) {
            return $cached['value'];
        }
        $resp = $this->call('POST', '/auth/login', ['email' => $this->email, 'password' => $this->password], null);
        if (! $resp['ok'] || empty($resp['body']['token'])) return null;
        $token = $resp['body']['token'];
        // Cache for ~23 hours
        $this->upsertSetting('_shiprocket', 'token', $token);
        $this->upsertSetting('_shiprocket', 'expires_at', date('Y-m-d H:i:s', strtotime('+23 hours')));
        return $token;
    }

    public function createOrder(int $orderId): array
    {
        $token = $this->token();
        if (! $token) return ['ok' => false, 'error' => 'Shiprocket not configured.'];

        $order = $this->db->table('orders')->where('id', $orderId)->get()->getRowArray();
        if (! $order) return ['ok' => false, 'error' => 'Order not found.'];
        $items = $this->db->table('order_items')->where('order_id', $orderId)->get()->getResultArray();
        $ship = json_decode($order['shipping_address'] ?? '{}', true) ?: [];

        $body = [
            'order_id'             => $order['order_number'],
            'order_date'           => date('Y-m-d H:i', strtotime($order['placed_at'] ?? $order['created_at'])),
            'pickup_location'      => 'Primary',
            'channel_id'           => $this->channelId,
            'billing_customer_name'=> $order['name'],
            'billing_last_name'    => '',
            'billing_address'      => $ship['line1'] ?? '',
            'billing_address_2'    => $ship['line2'] ?? '',
            'billing_city'         => $ship['city'] ?? '',
            'billing_state'        => $ship['state'] ?? '',
            'billing_pincode'      => $ship['pincode'] ?? '',
            'billing_country'      => 'India',
            'billing_email'        => $order['email'],
            'billing_phone'        => preg_replace('/\D/', '', $order['phone']),
            'shipping_is_billing'  => true,
            'order_items'          => array_map(function ($i) {
                $snap = json_decode($i['product_snapshot'] ?? '{}', true) ?: [];
                return [
                    'name'      => $snap['name'] ?? 'Item',
                    'sku'       => $snap['sku'] ?? (string) $i['variant_id'],
                    'units'     => (int) $i['qty'],
                    'selling_price' => (int) round(($i['unit_price'] ?? 0) / 100),
                ];
            }, $items),
            'payment_method'       => in_array($order['payment_method'], ['cod','partial_cod'], true) ? 'COD' : 'Prepaid',
            'sub_total'            => round($order['subtotal'] / 100, 2),
            'length'               => 10, 'breadth' => 10, 'height' => 10, 'weight' => 0.5,
        ];

        $resp = $this->call('POST', '/orders/create/adhoc', $body, $token);
        if (! $resp['ok']) return $resp;
        return ['ok' => true, 'shiprocket_order_id' => $resp['body']['order_id'] ?? null, 'shipment_id' => $resp['body']['shipment_id'] ?? null];
    }

    public function generateAwb(int $shipmentId, ?int $courierId = null): array
    {
        $token = $this->token();
        if (! $token) return ['ok' => false, 'error' => 'Not configured.'];
        $body = ['shipment_id' => $shipmentId];
        if ($courierId) $body['courier_id'] = $courierId;
        return $this->call('POST', '/courier/assign/awb', $body, $token);
    }

    public function requestPickup(int $shipmentId): array
    {
        $token = $this->token();
        if (! $token) return ['ok' => false, 'error' => 'Not configured.'];
        return $this->call('POST', '/courier/generate/pickup', ['shipment_id' => [$shipmentId]], $token);
    }

    public function trackByAwb(string $awb): ?array
    {
        $token = $this->token();
        if (! $token) return null;
        $resp = $this->call('GET', '/courier/track/awb/' . urlencode($awb), [], $token);
        return $resp['ok'] ? $resp['body'] : null;
    }

    protected function upsertSetting(string $group, string $key, string $value): void
    {
        $exists = $this->db->table('settings')->where('group_key', $group)->where('key', $key)->countAllResults() > 0;
        if ($exists) {
            $this->db->table('settings')->where('group_key', $group)->where('key', $key)->update(['value' => $value]);
        } else {
            $this->db->table('settings')->insert(['group_key' => $group, 'key' => $key, 'value' => $value, 'value_type' => 'string', 'is_public' => 0]);
        }
    }

    protected function call(string $method, string $path, array $body = [], ?string $token = null): array
    {
        $ch = curl_init($this->base . $path);
        $headers = ['Content-Type: application/json'];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        if (! empty($body) && $method !== 'GET') curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode((string) $resp, true) ?: [];
        if ($code >= 400) return ['ok' => false, 'error' => $json['message'] ?? 'Shiprocket error', 'body' => $json, 'code' => $code];
        return ['ok' => true, 'body' => $json, 'code' => $code];
    }
}
