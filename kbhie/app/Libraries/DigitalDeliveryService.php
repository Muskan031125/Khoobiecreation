<?php

namespace App\Libraries;

use Config\Database;

/**
 * Issues per-buyer download tokens for digital products and validates them on access.
 *
 * Usage:
 *   on order paid → DigitalDeliveryService::issueForOrder($orderId)  // creates token rows
 *   in email      → use ::buildUrl($token)
 *   on /download/{token} → ::validateAndConsume($token) → file_url or null
 */
class DigitalDeliveryService
{
    private const TOKEN_BYTES = 24;       // 48 hex chars
    private const TTL_DAYS    = 90;       // can re-download for 90 days
    private const MAX_DLS     = 10;

    private $db;
    public function __construct() { $this->db = Database::connect(); }

    /**
     * On `order.paid`, scan order_items for digital products and create one
     * download row per file. Returns array of [{product_name, url}] for the email.
     */
    public function issueForOrder(int $orderId): array
    {
        $order = $this->db->table('orders')->where('id', $orderId)->get()->getRow();
        if (! $order) return [];

        $items = $this->db->table('order_items oi')
            ->select('oi.id AS order_item_id, oi.product_id, p.name AS product_name, p.type')
            ->join('products p', 'p.id = oi.product_id')
            ->where('oi.order_id', $orderId)
            ->where('p.type IN ("digital","course")', null, false)
            ->get()->getResultArray();

        $emailLinks = [];
        foreach ($items as $it) {
            // For "digital" products, pull file URL(s) from product_files (table may be empty in demo)
            $files = $this->db->table('product_files')->where('product_id', $it['product_id'])->get()->getResultArray();
            if (empty($files)) {
                // Demo fallback — synthesise a placeholder file URL so the token + email flow can still be tested
                $files = [['file_url' => 'https://www.africau.edu/images/default/sample.pdf', 'file_name' => 'sample.pdf', 'file_size_bytes' => null]];
            }
            foreach ($files as $f) {
                // De-dupe — don't issue twice for the same order_item + file
                $existing = $this->db->table('digital_downloads')
                    ->where('order_item_id', $it['order_item_id'])
                    ->where('file_url', $f['file_url'])->get()->getRow();
                if ($existing) {
                    $token = $existing->token;
                } else {
                    $token = bin2hex(random_bytes(self::TOKEN_BYTES));
                    $this->db->table('digital_downloads')->insert([
                        'order_id'        => $orderId,
                        'order_item_id'   => $it['order_item_id'],
                        'product_id'      => $it['product_id'],
                        'user_id'         => $order->user_id,
                        'file_url'        => $f['file_url'],
                        'file_name'       => $f['file_name'] ?? basename(parse_url($f['file_url'], PHP_URL_PATH)),
                        'file_size_bytes' => $f['file_size_bytes'] ?? null,
                        'token'           => $token,
                        'max_downloads'   => self::MAX_DLS,
                        'expires_at'      => date('Y-m-d H:i:s', strtotime('+' . self::TTL_DAYS . ' days')),
                    ]);
                }
                $emailLinks[] = [
                    'product_name' => $it['product_name'],
                    'file_name'    => $f['file_name'] ?? basename(parse_url($f['file_url'], PHP_URL_PATH)),
                    'url'          => self::buildUrl($token),
                ];
            }
        }
        return $emailLinks;
    }

    public static function buildUrl(string $token): string
    {
        return rtrim(base_url(), '/') . '/download/' . $token;
    }

    /**
     * Validates the token and returns the file URL to serve (or null on fail).
     * Increments download counter on success.
     */
    public function validateAndConsume(string $token): ?array
    {
        $row = $this->db->table('digital_downloads')->where('token', $token)->get()->getRow();
        if (! $row) return ['error' => 'Invalid download link.'];
        if ($row->expires_at && strtotime($row->expires_at) < time()) return ['error' => 'This download link has expired.'];
        if ($row->downloads_count >= $row->max_downloads) return ['error' => "Download limit ({$row->max_downloads}) reached. Contact support if you need more."];

        $now = date('Y-m-d H:i:s');
        $this->db->table('digital_downloads')->where('id', $row->id)->update([
            'downloads_count'     => $row->downloads_count + 1,
            'first_downloaded_at' => $row->first_downloaded_at ?: $now,
            'last_downloaded_at'  => $now,
        ]);

        return [
            'file_url'  => $row->file_url,
            'file_name' => $row->file_name,
        ];
    }

    /** Customer-facing list of all their downloads, for /account/downloads. */
    public function listForUser(int $userId): array
    {
        return $this->db->table('digital_downloads dd')
            ->select('dd.token, dd.file_name, dd.downloads_count, dd.max_downloads, dd.expires_at, dd.created_at, p.name AS product_name')
            ->join('products p', 'p.id = dd.product_id')
            ->where('dd.user_id', $userId)
            ->orderBy('dd.created_at', 'DESC')
            ->get()->getResultArray();
    }
}
