<?php

namespace App\Libraries\Auth;

use Config\Database;

/**
 * Lockout policy:
 *   - 5 failed logins for the same identifier in 15 min → lock that account for 30 min
 *   - 10 failed attempts from the same IP in 15 min → lock the IP for 60 min
 *   - Successful login clears recent failures
 */
class LockoutService
{
    protected $db;

    public const ACCOUNT_THRESHOLD = 5;
    public const ACCOUNT_WINDOW    = 900;   // 15 min
    public const ACCOUNT_LOCK_FOR  = 1800;  // 30 min
    public const IP_THRESHOLD      = 10;
    public const IP_WINDOW         = 900;
    public const IP_LOCK_FOR       = 3600;  // 60 min

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function recordAttempt(string $identifier, string $kind, string $ip, ?string $ua, bool $success, ?string $reason = null, ?int $userId = null): void
    {
        $this->db->table('login_attempts')->insert([
            'identifier' => substr($identifier, 0, 191),
            'user_id'    => $userId,
            'kind'       => $kind,
            'ip'         => $ip,
            'user_agent' => $ua ? substr($ua, 0, 500) : null,
            'success'    => $success ? 1 : 0,
            'reason'     => $reason,
        ]);

        if ($success) {
            // Wipe recent failed attempts for this identifier — clean slate
            $this->db->table('lockouts')
                ->where('identifier', $identifier)
                ->where('type', 'account')
                ->where('unlocked_at', null)
                ->update(['unlocked_at' => date('Y-m-d H:i:s')]);
            return;
        }

        $this->maybeLockAccount($identifier, $reason);
        $this->maybeLockIp($ip, $reason);
    }

    public function isLocked(string $identifier, string $ip): array
    {
        $now = date('Y-m-d H:i:s');
        $acct = $this->db->table('lockouts')
            ->where('identifier', $identifier)->where('type', 'account')
            ->where('expires_at >=', $now)->where('unlocked_at', null)
            ->get()->getRowArray();
        if ($acct) return ['locked' => true, 'scope' => 'account', 'until' => $acct['expires_at'], 'reason' => $acct['reason']];

        $ipLock = $this->db->table('lockouts')
            ->where('identifier', $ip)->where('type', 'ip')
            ->where('expires_at >=', $now)->where('unlocked_at', null)
            ->get()->getRowArray();
        if ($ipLock) return ['locked' => true, 'scope' => 'ip', 'until' => $ipLock['expires_at'], 'reason' => $ipLock['reason']];

        return ['locked' => false];
    }

    protected function maybeLockAccount(string $identifier, ?string $reason): void
    {
        $since = date('Y-m-d H:i:s', time() - self::ACCOUNT_WINDOW);
        $fails = (int) $this->db->table('login_attempts')
            ->where('identifier', $identifier)
            ->where('success', 0)
            ->where('created_at >=', $since)
            ->countAllResults();
        if ($fails >= self::ACCOUNT_THRESHOLD) {
            $this->createLock($identifier, 'account', self::ACCOUNT_LOCK_FOR, $fails, "Too many failed attempts ({$fails}) — account locked");
        }
    }

    protected function maybeLockIp(string $ip, ?string $reason): void
    {
        $since = date('Y-m-d H:i:s', time() - self::IP_WINDOW);
        $fails = (int) $this->db->table('login_attempts')
            ->where('ip', $ip)
            ->where('success', 0)
            ->where('created_at >=', $since)
            ->countAllResults();
        if ($fails >= self::IP_THRESHOLD) {
            $this->createLock($ip, 'ip', self::IP_LOCK_FOR, $fails, "Too many failed attempts from this IP ({$fails})");
        }
    }

    protected function createLock(string $identifier, string $type, int $forSeconds, int $attempts, string $reason): void
    {
        $existing = $this->db->table('lockouts')
            ->where('identifier', $identifier)->where('type', $type)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->where('unlocked_at', null)
            ->countAllResults();
        if ($existing > 0) return; // already locked
        $this->db->table('lockouts')->insert([
            'identifier' => $identifier,
            'type'       => $type,
            'attempts'   => $attempts,
            'reason'     => $reason,
            'expires_at' => date('Y-m-d H:i:s', time() + $forSeconds),
        ]);
    }
}
