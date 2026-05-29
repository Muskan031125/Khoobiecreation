<?php

namespace App\Libraries\Rewards;

use Config\Database;

/**
 * Centralised reward issuance: loyalty points + personal coupons.
 *
 * Sources are settings rows so amounts can be tuned without code changes:
 *   rewards.verify_phone.points        (default 100)
 *   rewards.verify_phone.coupon_pct    (default 10, max-discount paise: 10000)
 *   rewards.verify_phone.coupon_max    (default 10000 paise = ₹100)
 *   rewards.verify_email.points        (default 50)
 *   rewards.verify_email.coupon_pct    (default 5)
 *   rewards.verify_email.coupon_max    (default 5000 paise = ₹50)
 *   rewards.fully_verified.bonus_pts   (default 100)
 *   rewards.first_review.points        (default 50)
 *   rewards.referral.referrer_pts      (default 200)
 *   rewards.referral.referred_pts      (default 100)
 */
class RewardService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Award reward for a named event. Idempotent — won't double-award.
     * Returns ['points' => N, 'coupon' => 'KK-...' or null, 'message' => string]
     */
    public function award(int $userId, string $event, array $context = []): array
    {
        // Idempotency: skip if same event already recorded for this user
        if ($this->alreadyAwarded($userId, $event, $context)) {
            return ['ok' => false, 'reason' => 'already_awarded'];
        }

        $points = 0;
        $couponCode = null;
        $couponMessage = '';

        switch ($event) {
            case 'verify_phone':
                $points = (int) $this->setting('rewards.verify_phone.points', 100);
                $couponCode = $this->createPersonalCoupon(
                    $userId,
                    (int) $this->setting('rewards.verify_phone.coupon_pct', 10),
                    (int) $this->setting('rewards.verify_phone.coupon_max', 10000),
                    'phone verification',
                    'PHV'
                );
                $couponMessage = "10% off (max ₹100)";
                break;

            case 'verify_email':
                $points = (int) $this->setting('rewards.verify_email.points', 50);
                $couponCode = $this->createPersonalCoupon(
                    $userId,
                    (int) $this->setting('rewards.verify_email.coupon_pct', 5),
                    (int) $this->setting('rewards.verify_email.coupon_max', 5000),
                    'email verification',
                    'EMV'
                );
                $couponMessage = "5% off (max ₹50)";
                break;

            case 'fully_verified':
                $points = (int) $this->setting('rewards.fully_verified.bonus_pts', 100);
                break;

            case 'first_review':
                $points = (int) $this->setting('rewards.first_review.points', 50);
                break;

            case 'referral_signup':
                $points = (int) $this->setting('rewards.referral.referrer_pts', 200);
                break;

            default:
                return ['ok' => false, 'reason' => 'unknown_event'];
        }

        if ($points > 0) {
            $this->grantPoints($userId, $points, $event, $context);
        }

        return [
            'ok'             => true,
            'points'         => $points,
            'coupon_code'    => $couponCode,
            'coupon_message' => $couponMessage,
            'message'        => $this->humanMessage($event, $points, $couponCode, $couponMessage),
        ];
    }

    public function grantPoints(int $userId, int $points, string $reason, array $context = []): int
    {
        // Get or create loyalty account
        $acct = $this->db->table('loyalty_accounts')->where('user_id', $userId)->get()->getRowArray();
        if (! $acct) {
            $this->db->table('loyalty_accounts')->insert([
                'user_id'        => $userId,
                'points_balance' => 0,
                'lifetime_points'=> 0,
                'tier'           => 'bronze',
            ]);
            $acct = $this->db->table('loyalty_accounts')->where('user_id', $userId)->get()->getRowArray();
        }

        $newBalance = (int) $acct['points_balance'] + $points;
        $newLifetime= (int) $acct['lifetime_points'] + max(0, $points);

        $this->db->table('loyalty_transactions')->insert([
            'user_id'       => $userId,
            'points_change' => $points,
            'balance_after' => $newBalance,
            'reason'        => $this->ledgerReason($reason),
            'ref_type'      => 'reward',
            'ref_id'        => null,
            'note'          => "Reward: {$reason}" . (! empty($context['ref']) ? " ({$context['ref']})" : ''),
            'expires_at'    => date('Y-m-d H:i:s', strtotime('+365 days')),
        ]);

        $this->db->table('loyalty_accounts')->where('user_id', $userId)->update([
            'points_balance'  => $newBalance,
            'lifetime_points' => $newLifetime,
        ]);

        return $newBalance;
    }

    /** Create a personal coupon restricted to this user. Returns the code. */
    public function createPersonalCoupon(int $userId, int $pct, int $maxDiscountPaise, string $reasonLabel, string $codePrefix = 'KB'): string
    {
        // Build the promotion (one promo per coupon, since rewards are per-user)
        $this->db->table('promotions')->insert([
            'name'              => "Personal reward — {$reasonLabel}",
            'description'       => "Auto-issued for {$reasonLabel}",
            'type'              => 'percent_off',
            'scope'             => 'cart',
            'priority'          => 150,
            'rules'             => json_encode([]),
            'rewards'           => json_encode(['type' => 'percent_off', 'value' => $pct, 'max_discount' => $maxDiscountPaise]),
            'stackable'         => 0,
            'auto_apply'        => 0,
            'requires_coupon'   => 1,
            'is_active'         => 1,
            'starts_at'         => date('Y-m-d H:i:s'),
            'ends_at'           => date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);
        $promoId = (int) $this->db->insertID();

        // Build a unique, unguessable code
        $code = strtoupper($codePrefix) . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $this->db->table('coupons')->insert([
            'code'                 => $code,
            'promotion_id'         => $promoId,
            'max_uses'             => 1,
            'max_uses_per_user'    => 1,
            'is_single_use'        => 1,
            'restricted_to_user_id'=> $userId,
            'ends_at'              => date('Y-m-d H:i:s', strtotime('+30 days')),
            'is_active'            => 1,
        ]);
        return $code;
    }

    protected function alreadyAwarded(int $userId, string $event, array $context): bool
    {
        // Reward events share enum reasons (e.g. verify_phone + verify_email both
        // map to 'signup_bonus'), so we look at the note column where we recorded
        // the specific event name. This makes alreadyAwarded() per-event accurate.
        return $this->db->table('loyalty_transactions')
            ->where('user_id', $userId)
            ->like('note', "Reward: {$event}", 'after')
            ->countAllResults() > 0;
    }

    protected function ledgerReason(string $event): string
    {
        return match ($event) {
            'verify_phone', 'verify_email', 'fully_verified' => 'signup_bonus', // tracked via note for idempotency
            'first_review'    => 'review',
            'referral_signup' => 'referral',
            default           => 'manual',
        };
    }

    protected function humanMessage(string $event, int $points, ?string $coupon, string $couponMessage): string
    {
        $bits = [];
        if ($points > 0) $bits[] = "+{$points} Khoobie Points";
        if ($coupon)     $bits[] = "personal coupon {$coupon} ({$couponMessage})";
        return 'Earned: ' . implode(' and ', $bits);
    }

    protected function setting(string $key, $default = null)
    {
        [$group, $rest] = explode('.', $key, 2);
        $row = $this->db->table('settings')->where('group_key', $group)->where('key', $rest)->get()->getRowArray();
        if (! $row || $row['value'] === '' || $row['value'] === null) return $default;
        return $row['value'];
    }
}
