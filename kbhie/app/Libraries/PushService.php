<?php

namespace App\Libraries;

use Config\Database;
use Config\Services;

/**
 * Web-push delivery.
 *
 * Storage: push_subscriptions table.
 * Delivery: stub for now — drop in minishlink/web-push composer package later
 * and uncomment the actual Push call. Until then, all sends are logged so the
 * flow works end-to-end in dev.
 *
 * Server-side env vars needed for production:
 *   push.vapid_public  = ...
 *   push.vapid_private = ...
 *   push.vapid_subject = mailto:ops@khoobie.com
 */
class PushService
{
    public function saveSubscription(array $subscription, ?int $userId = null): array
    {
        $db = Database::connect();
        $req = Services::request();
        $anonId = $req->getCookie('kb_anon') ?: ('sess_' . session_id());

        $endpoint = $subscription['endpoint'] ?? '';
        if (! $endpoint) return ['ok' => false, 'error' => 'Missing endpoint'];

        // Upsert by unique endpoint
        $existing = $db->table('push_subscriptions')->where('endpoint', $endpoint)->get()->getRow();
        $data = [
            'user_id'    => $userId,
            'anon_id'    => $anonId,
            'endpoint'   => $endpoint,
            'p256dh_key' => $subscription['keys']['p256dh'] ?? '',
            'auth_token' => $subscription['keys']['auth']   ?? '',
            'user_agent' => substr((string) $req->getUserAgent(), 0, 500),
            'is_active'  => 1,
        ];
        if ($existing) $db->table('push_subscriptions')->where('id', $existing->id)->update($data);
        else           $db->table('push_subscriptions')->insert($data);
        return ['ok' => true];
    }

    /** Send a push to one user across all their active subscriptions. */
    public function sendTo(int $userId, string $title, string $body, string $url = '/'): int
    {
        $subs = Database::connect()->table('push_subscriptions')
            ->where('user_id', $userId)->where('is_active', 1)
            ->get()->getResultArray();
        return $this->dispatchAll($subs, $title, $body, $url);
    }

    /** Broadcast to everyone — used by campaigns + alerts. */
    public function broadcast(string $title, string $body, string $url = '/'): int
    {
        $subs = Database::connect()->table('push_subscriptions')->where('is_active', 1)->get()->getResultArray();
        return $this->dispatchAll($subs, $title, $body, $url);
    }

    private function dispatchAll(array $subs, string $title, string $body, string $url): int
    {
        if (empty($subs)) return 0;

        $payload = json_encode(['title' => $title, 'body' => $body, 'url' => $url, 'icon' => base_url('assets/brand/logo.png')]);
        $sent = 0;
        foreach ($subs as $s) {
            // PRODUCTION: $webPush->queueNotification(Subscription::create([...]), $payload);
            // For now: log so the flow is traceable
            log_message('info', "[push] {$s['endpoint']} ← {$payload}");
            $sent++;
        }
        // PRODUCTION: foreach ($webPush->flush() as $report) { update is_active on 410 Gone }
        return $sent;
    }
}
