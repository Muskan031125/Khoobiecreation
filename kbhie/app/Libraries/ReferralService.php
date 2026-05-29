<?php

namespace App\Libraries;

use Config\Database;
use Config\Services;

/**
 * Referral program operations.
 *   - mintCodeForUser(): one-time, called on signup
 *   - landByCode($code, $utm): visitor clicked /r/{code} — cookie + tracking row
 *   - attachOnSignup($newUserId): convert cookie referrer into a real referee link
 *   - rewardOnFirstOrder($userId, $orderId, $amount): award points + coupon
 *   - dashboard($userId): everything the account page shows
 */
class ReferralService
{
    private const COOKIE         = 'kb_ref';
    private const POINTS_REWARD  = 200;
    private const REFEREE_COUPON = 'WELCOME10'; // reuse the existing welcome coupon

    private $db;
    public function __construct() { $this->db = Database::connect(); }

    /** Generate "{FIRST4}-{RANDOM4}" unique code, persist on user. */
    public function mintCodeForUser(int $userId, string $name): string
    {
        $user = $this->db->table('users')->where('id', $userId)->get()->getRow();
        if ($user && ! empty($user->referral_code)) return $user->referral_code;

        $first = preg_replace('/[^A-Z]/', '', strtoupper($name));
        $first = substr($first ?: 'KHOO', 0, 4);
        for ($i = 0; $i < 5; $i++) {
            $code = $first . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            if (! $this->db->table('users')->where('referral_code', $code)->countAllResults()) {
                $this->db->table('users')->where('id', $userId)->update(['referral_code' => $code]);
                return $code;
            }
        }
        // Fallback if 5 collisions (extremely unlikely)
        $code = 'KB-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $this->db->table('users')->where('id', $userId)->update(['referral_code' => $code]);
        return $code;
    }

    /**
     * Called when a visitor hits /r/{code}. Drops a 30-day cookie and writes
     * a `referrals` row in 'cookied' status so we can measure attribution later.
     */
    public function landByCode(string $code, array $utm = []): array
    {
        $code = strtoupper(trim($code));
        $referrer = $this->db->table('users')->where('referral_code', $code)->get()->getRow();
        if (! $referrer) return ['ok' => false, 'error' => 'Unknown referral code.'];

        $resp = Services::response();
        $resp->setCookie(self::COOKIE, $code, 60 * 60 * 24 * 30); // 30 days

        $request = Services::request();
        $anonId  = $request->getCookie('kb_anon') ?: ('sess_' . session_id());

        // Deduplicate within this anon session
        $existing = $this->db->table('referrals')
            ->where('referrer_user_id', $referrer->id)
            ->where('referee_anon_id', $anonId)
            ->where('status', 'cookied')
            ->countAllResults();
        if (! $existing) {
            $this->db->table('referrals')->insert([
                'referrer_user_id' => $referrer->id,
                'referee_anon_id'  => $anonId,
                'code_used'        => $code,
                'status'           => 'cookied',
                'channel'          => $utm['channel']  ?? null,
                'utm_source'       => $utm['source']   ?? null,
                'utm_medium'       => $utm['medium']   ?? null,
                'utm_campaign'     => $utm['campaign'] ?? null,
            ]);
        }
        return ['ok' => true, 'referrer' => ['id' => $referrer->id, 'name' => $referrer->name]];
    }

    /**
     * On signup: if the new user came via a referral cookie, attach the link.
     * Flip the referral row from 'cookied' to 'signed_up'.
     */
    public function attachOnSignup(int $newUserId): bool
    {
        $req  = Services::request();
        $code = $req->getCookie(self::COOKIE);
        if (! $code) return false;

        $referrer = $this->db->table('users')->where('referral_code', $code)->get()->getRow();
        if (! $referrer || $referrer->id === $newUserId) return false;

        $this->db->table('users')->where('id', $newUserId)->update(['referred_by_user_id' => $referrer->id]);

        $anonId = $req->getCookie('kb_anon') ?: ('sess_' . session_id());
        $row = $this->db->table('referrals')
            ->where('referrer_user_id', $referrer->id)
            ->where('referee_anon_id', $anonId)
            ->where('status', 'cookied')
            ->orderBy('id', 'DESC')->limit(1)->get()->getRow();
        if ($row) {
            $this->db->table('referrals')->where('id', $row->id)->update([
                'referee_user_id' => $newUserId,
                'status'          => 'signed_up',
                'signed_up_at'    => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Cookie present but no row — still record the attribution
            $this->db->table('referrals')->insert([
                'referrer_user_id' => $referrer->id,
                'referee_user_id'  => $newUserId,
                'referee_anon_id'  => $anonId,
                'code_used'        => $code,
                'status'           => 'signed_up',
                'signed_up_at'     => date('Y-m-d H:i:s'),
            ]);
        }
        return true;
    }

    /**
     * Called after a successful payment on the referee's FIRST order.
     * Awards points to referrer + records the conversion.
     */
    public function rewardOnFirstOrder(int $refereeUserId, int $orderId, int $amountPaise): bool
    {
        // Only reward for the very first order
        $orderCount = $this->db->table('orders')
            ->where('user_id', $refereeUserId)
            ->where('status', 'paid')
            ->countAllResults();
        if ($orderCount > 1) return false;

        $row = $this->db->table('referrals')
            ->where('referee_user_id', $refereeUserId)
            ->whereIn('status', ['signed_up', 'cookied'])
            ->orderBy('id', 'DESC')->limit(1)->get()->getRow();
        if (! $row) return false;

        $this->db->table('referrals')->where('id', $row->id)->update([
            'first_order_id'     => $orderId,
            'first_order_amount' => $amountPaise,
            'referrer_points'    => self::POINTS_REWARD,
            'status'             => 'rewarded',
            'converted_at'       => date('Y-m-d H:i:s'),
            'rewarded_at'        => date('Y-m-d H:i:s'),
        ]);

        // Credit referrer's loyalty account
        $this->db->query(
            "INSERT INTO loyalty_accounts (user_id, points_balance, lifetime_points)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 points_balance = points_balance + VALUES(points_balance),
                 lifetime_points = lifetime_points + VALUES(points_balance)",
            [$row->referrer_user_id, self::POINTS_REWARD, self::POINTS_REWARD]
        );
        $this->db->table('loyalty_transactions')->insert([
            'user_id'   => $row->referrer_user_id,
            'kind'      => 'earn',
            'points'    => self::POINTS_REWARD,
            'reason'    => 'referral_conversion',
            'ref_type'  => 'order',
            'ref_id'    => $orderId,
            'note'      => "Referral reward — {$row->code_used}",
        ]);
        return true;
    }

    /** Aggregated stats for the account dashboard widget. */
    public function dashboard(int $userId): array
    {
        $user = $this->db->table('users')->where('id', $userId)->get()->getRow();
        if (! $user) return ['code' => null];
        $code = $user->referral_code ?: $this->mintCodeForUser($userId, $user->name);

        $rows = $this->db->table('referrals')->where('referrer_user_id', $userId)->get()->getResultArray();
        $signedUp = count(array_filter($rows, fn ($r) => in_array($r['status'], ['signed_up','rewarded'], true)));
        $rewarded = count(array_filter($rows, fn ($r) => $r['status'] === 'rewarded'));
        $points   = array_sum(array_map(fn ($r) => (int) $r['referrer_points'], $rows));

        return [
            'code'           => $code,
            'link'           => rtrim(base_url(), '/') . '/r/' . $code,
            'total_clicks'   => count($rows),
            'signed_up'      => $signedUp,
            'converted'      => $rewarded,
            'points_earned'  => $points,
            'referee_coupon' => self::REFEREE_COUPON,
            'reward_amount'  => self::POINTS_REWARD,
        ];
    }
}
