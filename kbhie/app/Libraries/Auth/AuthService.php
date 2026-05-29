<?php

namespace App\Libraries\Auth;

use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class AuthService
{
    protected UserModel $users;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->db    = Database::connect();
    }

    public function register(array $data, array $roles = ['customer']): array
    {
        $existing = null;
        if (! empty($data['email'])) {
            $existing = $this->users->where('email', $data['email'])->first();
        }
        if (! $existing && ! empty($data['phone'])) {
            $existing = $this->users->where('phone', $data['phone'])->first();
        }
        if ($existing) {
            return ['ok' => false, 'error' => 'An account already exists with this email or phone. Try logging in.'];
        }

        $userId = $this->users->insert([
            'email'         => $data['email']    ?? null,
            'phone'         => $data['phone']    ?? null,
            'name'          => $data['name']     ?? null,
            'password_hash' => isset($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : null,
            'status'        => 'active',
        ], true);

        foreach ($roles as $role) {
            $this->users->attachRole((int) $userId, $role);
        }

        // Create loyalty account
        $this->db->table('loyalty_accounts')->insert([
            'user_id'        => $userId,
            'points_balance' => 0,
            'tier'           => 'bronze',
        ]);

        // Award signup bonus from loyalty_rules
        $rule = $this->db->table('loyalty_rules')->where(['event' => 'signup', 'is_active' => 1])->get()->getRow();
        if ($rule) {
            $points = (int) $rule->points_formula;
            if ($points > 0) {
                $this->db->table('loyalty_transactions')->insert([
                    'user_id'       => $userId,
                    'points_change' => $points,
                    'balance_after' => $points,
                    'reason'        => 'signup_bonus',
                    'note'          => 'Welcome bonus',
                    'expires_at'    => $rule->expires_days ? date('Y-m-d H:i:s', strtotime("+{$rule->expires_days} days")) : null,
                ]);
                $this->db->table('loyalty_accounts')
                    ->where('user_id', $userId)
                    ->update(['points_balance' => $points, 'lifetime_points' => $points]);
            }
        }

        return ['ok' => true, 'user_id' => (int) $userId];
    }

    public function attemptLogin(string $identifier, string $password, string $kind = 'login_pwd'): array
    {
        $req = \Config\Services::request();
        $ip  = $req->getIPAddress();
        $ua  = $req->getUserAgent();
        $lock = new LockoutService();

        // 1. Check lockouts first
        $check = $lock->isLocked($identifier, $ip);
        if ($check['locked']) {
            $remain = max(0, strtotime($check['until']) - time());
            $mins = (int) ceil($remain / 60);
            return ['ok' => false, 'error' => "Too many failed attempts. Try again in {$mins} minute" . ($mins === 1 ? '' : 's') . '.'];
        }

        $user = $this->users->findByEmailOrPhone($identifier);
        if (! $user || ! $user['password_hash'] || ! password_verify($password, $user['password_hash'])) {
            $lock->recordAttempt($identifier, $kind, $ip, (string) $ua, false, 'invalid_credentials', $user['id'] ?? null);
            return ['ok' => false, 'error' => 'Invalid credentials.'];
        }
        if ($user['status'] !== 'active') {
            $lock->recordAttempt($identifier, $kind, $ip, (string) $ua, false, 'inactive', $user['id']);
            return ['ok' => false, 'error' => 'This account is not active. Contact support.'];
        }

        $lock->recordAttempt($identifier, $kind, $ip, (string) $ua, true, null, $user['id']);
        $this->establishSession($user);
        return ['ok' => true, 'user_id' => (int) $user['id']];
    }

    public function establishSession(array $user): void
    {
        $roles = $this->users->getRoles((int) $user['id']);
        $perms = $this->resolvePermissions($roles);
        session()->set('user', [
            'id'          => (int) $user['id'],
            'name'        => $user['name']  ?? '',
            'email'       => $user['email'] ?? '',
            'phone'       => $user['phone'] ?? '',
            'roles'       => $roles,
            'permissions' => $perms,
        ]);
        $this->users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    public function logout(): void
    {
        session()->remove('user');
        session()->destroy();
    }

    public function currentUser(): ?array
    {
        return session('user');
    }

    /**
     * Generate a single OTP for $identifier, store it once, and dispatch the
     * same code across one or more channels (sms / whatsapp / email).
     *
     * @param string|string[] $channels  e.g. 'sms', ['whatsapp','sms'], 'email'
     */
    public function generateOtp(string $identifier, string|array $channels = 'sms', string $purpose = 'login'): string
    {
        $channelList = is_array($channels) ? $channels : [$channels];
        $primary = $channelList[0] ?? 'sms';

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->db->table('auth_otps')->insert([
            'identifier' => $identifier,
            'channel'    => $primary,
            'code_hash'  => password_hash($code, PASSWORD_BCRYPT),
            'purpose'    => $purpose,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
        ]);

        $purposeLabel = match ($purpose) {
            'verify_phone' => 'verify your phone',
            'verify_email' => 'verify your email',
            'login'        => 'sign in',
            default        => 'continue',
        };

        $notif = new \App\Libraries\Notifications\NotificationService();
        foreach ($channelList as $ch) {
            try {
                $notif->send($ch, $identifier, 'otp', ['code' => $code, 'purpose' => $purposeLabel]);
            } catch (\Throwable $e) {
                log_message('error', "OTP dispatch failed [$ch]: " . $e->getMessage());
            }
        }

        log_message('info', "OTP for {$identifier} ({$primary}/{$purpose}): {$code}");
        return $code; // dev convenience; production responses must never include it
    }

    public function verifyOtp(string $identifier, string $code, string $purpose = 'login'): bool
    {
        $row = $this->db->table('auth_otps')
            ->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('used_at', null)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();
        if (! $row) return false;
        if (! password_verify($code, $row->code_hash)) {
            $this->db->table('auth_otps')->where('id', $row->id)->update(['attempts' => (int) $row->attempts + 1]);
            return false;
        }
        $this->db->table('auth_otps')->where('id', $row->id)->update(['used_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /** Flatten roles → permission array. super_admin gets ['*']. */
    public function resolvePermissions(array $roleNames): array
    {
        if (empty($roleNames)) return [];
        if (in_array('super_admin', $roleNames, true)) return ['*'];
        $rows = $this->db->table('roles')->whereIn('name', $roleNames)->get()->getResultArray();
        $perms = [];
        foreach ($rows as $r) {
            $list = json_decode($r['permissions'] ?? '[]', true) ?: [];
            foreach ($list as $p) $perms[$p] = true;
        }
        return array_keys($perms);
    }

    public function loginByPhone(string $phone): ?array
    {
        $user = $this->users->where('phone', $phone)->first();
        if (! $user) {
            $id = $this->users->insert([
                'phone'              => $phone,
                'name'               => 'Khoobie Friend',
                'status'             => 'active',
                'phone_verified_at'  => date('Y-m-d H:i:s'),
            ], true);
            $this->users->attachRole((int) $id, 'customer');
            $user = $this->users->find($id);
        }
        $this->establishSession($user);
        return $user;
    }
}
