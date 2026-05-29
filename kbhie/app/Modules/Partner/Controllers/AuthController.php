<?php

namespace App\Modules\Partner\Controllers;

use App\Libraries\Auth\AuthService;

class AuthController extends BasePartnerController
{
    public function login()
    {
        if (session('user') && in_array('partner', session('user')['roles'] ?? [], true)) {
            return redirect()->to('/partner');
        }
        return view('App\Modules\Partner\Views\login', [
            'page' => ['title' => 'Partner Login — Khoobie'],
        ]);
    }

    public function loginPost()
    {
        $identifier = trim((string) $this->request->getPost('identifier'));
        $password   = (string) $this->request->getPost('password');
        $auth = new AuthService();
        $res = $auth->attemptLogin($identifier, $password);
        if (! $res['ok']) {
            return redirect()->back()->withInput()->with('error', $res['error']);
        }
        if (! in_array('partner', session('user')['roles'] ?? [], true)) {
            $auth->logout();
            return redirect()->back()->with('error', 'This account is not registered as a partner.');
        }
        return redirect()->to('/partner');
    }

    public function logout()
    {
        (new AuthService())->logout();
        return redirect()->to('/partner/login');
    }
}
