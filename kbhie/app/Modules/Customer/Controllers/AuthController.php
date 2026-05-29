<?php

namespace App\Modules\Customer\Controllers;

use App\Libraries\Auth\AuthService;
use App\Modules\Storefront\Controllers\BaseStoreController;

class AuthController extends BaseStoreController
{
    public function login()
    {
        if (session('user')) {
            return redirect()->to('/account');
        }
        return $this->view('App\Modules\Customer\Views\login', [
            'page' => array_merge($this->data['page'], [
                'title' => 'Log in — Krafty Khoobie',
            ]),
            'next' => $this->request->getGet('next') ?? '/account',
        ]);
    }

    public function loginPost()
    {
        $identifier = trim((string) $this->request->getPost('identifier'));
        $password   = (string) $this->request->getPost('password');
        $next       = $this->request->getPost('next') ?: '/account';

        $auth = new AuthService();
        $result = $auth->attemptLogin($identifier, $password);
        if (! $result['ok']) {
            return redirect()->back()->withInput()->with('error', $result['error']);
        }
        return redirect()->to($next);
    }

    public function signup()
    {
        if (session('user')) {
            return redirect()->to('/account');
        }
        return $this->view('App\Modules\Customer\Views\signup', [
            'page' => array_merge($this->data['page'], [
                'title' => 'Create your account — Krafty Khoobie',
            ]),
            // Prefill from lead capture if available
            'prefill' => session('lead_prefill') ?? [],
        ]);
    }

    public function signupPost()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email|max_length[191]',
            'phone'    => 'required|min_length[10]|max_length[20]',
            'password' => 'required|min_length[6]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $data = [
            'name'     => $this->request->getPost('name'),
            'email'    => $this->request->getPost('email'),
            'phone'    => $this->request->getPost('phone'),
            'password' => $this->request->getPost('password'),
        ];
        $auth = new AuthService();
        $res = $auth->register($data);
        if (! $res['ok']) {
            return redirect()->back()->withInput()->with('error', $res['error']);
        }
        $auth->attemptLogin($data['email'], $data['password']);

        // Welcome email + WhatsApp
        try {
            $notif = new \App\Libraries\Notifications\NotificationService();
            $payload = ['name' => $data['name'], 'shop_url' => base_url('shop')];
            $notif->send('email', $data['email'], 'welcome', $payload, $res['user_id']);
            if (! empty($data['phone'])) {
                $notif->send('whatsapp', $data['phone'], 'welcome', $payload, $res['user_id']);
            }
        } catch (\Throwable $e) { /* providers may be unconfigured locally */ }

        return redirect()->to('/account')->with('success', 'Welcome to Khoobie! Your account is ready.');
    }

    public function logout()
    {
        (new AuthService())->logout();
        return redirect()->to('/')->with('success', 'You\'ve been logged out.');
    }

    public function sendOtp()
    {
        $phone = trim((string) $this->request->getPost('phone'));
        if (! preg_match('/^\d{10,15}$/', preg_replace('/\D/', '', $phone))) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please enter a valid phone number.']);
        }
        $auth = new AuthService();
        $code = $auth->generateOtp($phone, 'sms', 'login');
        $env  = env('CI_ENVIRONMENT', 'development');
        return $this->response->setJSON([
            'ok'       => true,
            'message'  => 'OTP sent to ' . substr($phone, -4),
            'dev_code' => $env === 'development' ? $code : null,
        ]);
    }

    public function verifyOtp()
    {
        $phone = trim((string) $this->request->getPost('phone'));
        $code  = trim((string) $this->request->getPost('code'));
        $auth  = new AuthService();
        if (! $auth->verifyOtp($phone, $code, 'login')) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Invalid or expired code.']);
        }
        $auth->loginByPhone($phone);
        return $this->response->setJSON(['ok' => true, 'redirect' => '/account']);
    }
}
