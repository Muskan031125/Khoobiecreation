<?php

namespace App\Libraries;

use Config\Database;

/**
 * MembershipService — checks if the current user has an active membership
 * (Khoobie Insider etc.) and exposes the member discount percentage so we
 * can apply it across cart, PDP, and checkout.
 */
class MembershipService
{
    private const SESSION_KEY = 'membership_state';
    private const CACHE_TTL   = 600; // 10 minutes — cheap to recompute

    public static function current(): array
    {
        $user = session('user');
        if (! $user) return self::none();

        $cached = session(self::SESSION_KEY);
        if (is_array($cached) && ($cached['cached_at'] ?? 0) > time() - self::CACHE_TTL && ($cached['user_id'] ?? 0) === (int) $user['id']) {
            return $cached;
        }

        $db = Database::connect();
        $row = $db->query("
            SELECT s.id AS subscription_id, m.tier_name, m.discount_pct, m.free_shipping, m.bonus_points_pct, m.free_courses
            FROM subscriptions s
            JOIN subscription_plans sp ON sp.id = s.plan_id
            JOIN products p           ON p.id = sp.product_id
            JOIN memberships m        ON m.product_id = p.id
            WHERE s.user_id = ? AND s.status = 'active' AND p.type = 'membership'
            ORDER BY m.discount_pct DESC
            LIMIT 1
        ", [(int) $user['id']])->getRowArray();

        $state = $row ? [
            'active'           => true,
            'user_id'          => (int) $user['id'],
            'tier'             => $row['tier_name'],
            'discount_pct'     => (float) $row['discount_pct'],
            'free_shipping'    => (bool) $row['free_shipping'],
            'bonus_points_pct' => (float) $row['bonus_points_pct'],
            'free_courses'     => (bool) $row['free_courses'],
            'cached_at'        => time(),
        ] : self::none();

        session()->set(self::SESSION_KEY, $state);
        return $state;
    }

    private static function none(): array
    {
        return [
            'active' => false, 'user_id' => null, 'tier' => null,
            'discount_pct' => 0, 'free_shipping' => false,
            'bonus_points_pct' => 0, 'free_courses' => false,
            'cached_at' => time(),
        ];
    }

    /** Apply the member discount to a paise amount. */
    public static function applyDiscount(int $paise): int
    {
        $m = self::current();
        if (! $m['active'] || $m['discount_pct'] <= 0) return $paise;
        return (int) round($paise * (1 - $m['discount_pct'] / 100));
    }

    /** Tiny formatted member badge HTML — drops into PDP / cart. */
    public static function badge(): string
    {
        $m = self::current();
        if (! $m['active']) return '';
        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-violet-100 text-violet-700 text-[10px] font-black uppercase tracking-wider">⭐ ' . esc($m['tier']) . '</span>';
    }
}
