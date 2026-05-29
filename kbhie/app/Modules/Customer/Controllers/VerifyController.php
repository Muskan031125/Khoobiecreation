<?php

namespace App\Modules\Customer\Controllers;

use App\Libraries\Auth\AuthService;
use App\Libraries\Rewards\RewardService;
use App\Modules\Storefront\Controllers\BaseStoreController;
use Config\Database;

class VerifyController extends BaseStoreController
{
    public function sendPhone()
    {
        $user = session('user');
        if (! $user) return $this->jsonError('Please log in first.');
        if (! $user['phone']) return $this->jsonError('No phone number on file. Update your profile first.');

        $db = Database::connect();
        $verified = $db->table('users')->where('id', $user['id'])->select('phone_verified_at')->get()->getRowArray();
        if ($verified && $verified['phone_verified_at']) {
            return $this->jsonError('Phone already verified.');
        }

        // ONE OTP, dispatched to both channels
        $code = (new AuthService())->generateOtp($user['phone'], ['whatsapp', 'sms'], 'verify_phone');

        $env = env('CI_ENVIRONMENT', 'development');
        return $this->response->setJSON([
            'ok'       => true,
            'message'  => 'OTP sent via WhatsApp + SMS to ' . substr($user['phone'], -4),
            'dev_code' => $env === 'development' ? $code : null,
        ]);
    }

    public function confirmPhone()
    {
        $user = session('user');
        if (! $user) return $this->jsonError('Please log in first.');

        $code = preg_replace('/\D/', '', (string) $this->request->getPost('code'));
        if (strlen($code) !== 6) return $this->jsonError('Enter the 6-digit code.');

        $auth = new AuthService();
        if (! $auth->verifyOtp($user['phone'], $code, 'verify_phone')) {
            return $this->jsonError('Invalid or expired code.');
        }

        $db = Database::connect();
        $db->table('users')->where('id', $user['id'])->update(['phone_verified_at' => date('Y-m-d H:i:s')]);

        $reward = (new RewardService())->award((int) $user['id'], 'verify_phone');
        $this->maybeAwardFullyVerified((int) $user['id']);

        return $this->response->setJSON([
            'ok'      => true,
            'message' => '🎉 Phone verified!',
            'reward'  => $reward,
        ]);
    }

    public function sendEmail()
    {
        $user = session('user');
        if (! $user) return $this->jsonError('Please log in first.');
        if (! $user['email']) return $this->jsonError('No email address on file. Update your profile first.');

        $db = Database::connect();
        $verified = $db->table('users')->where('id', $user['id'])->select('email_verified_at')->get()->getRowArray();
        if ($verified && $verified['email_verified_at']) {
            return $this->jsonError('Email already verified.');
        }

        $code = (new AuthService())->generateOtp($user['email'], 'email', 'verify_email');
        $env = env('CI_ENVIRONMENT', 'development');
        return $this->response->setJSON([
            'ok'       => true,
            'message'  => 'OTP sent to ' . $user['email'],
            'dev_code' => $env === 'development' ? $code : null,
        ]);
    }

    public function confirmEmail()
    {
        $user = session('user');
        if (! $user) return $this->jsonError('Please log in first.');

        $code = preg_replace('/\D/', '', (string) $this->request->getPost('code'));
        if (strlen($code) !== 6) return $this->jsonError('Enter the 6-digit code.');

        $auth = new AuthService();
        if (! $auth->verifyOtp($user['email'], $code, 'verify_email')) {
            return $this->jsonError('Invalid or expired code.');
        }

        $db = Database::connect();
        $db->table('users')->where('id', $user['id'])->update(['email_verified_at' => date('Y-m-d H:i:s')]);

        $reward = (new RewardService())->award((int) $user['id'], 'verify_email');
        $this->maybeAwardFullyVerified((int) $user['id']);

        return $this->response->setJSON([
            'ok'      => true,
            'message' => '🎉 Email verified!',
            'reward'  => $reward,
        ]);
    }

    protected function maybeAwardFullyVerified(int $userId): void
    {
        $row = Database::connect()->table('users')->where('id', $userId)
            ->select('phone_verified_at, email_verified_at')->get()->getRowArray();
        if ($row && $row['phone_verified_at'] && $row['email_verified_at']) {
            (new RewardService())->award($userId, 'fully_verified', ['ref' => 'fully_verified']);
        }
    }

    protected function jsonError(string $msg)
    {
        return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => $msg]);
    }
}
