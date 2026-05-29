<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Auth\AuthService;

class AuthController extends BaseAdminController
{
    public function login()
    {
        if (session('user')) {
            $roles = session('user')['roles'] ?? [];
            if (array_intersect(['admin','super_admin','staff'], $roles)) {
                return redirect()->to('/admin');
            }
        }
        return view('App\Modules\Admin\Views\login', [
            'page' => ['title' => 'Admin Login — Khoobie'],
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
        $roles = session('user')['roles'] ?? [];
        if (! array_intersect(['admin','super_admin','staff'], $roles)) {
            (new AuthService())->logout();
            return redirect()->back()->with('error', 'This account does not have admin access.');
        }
        return redirect()->to('/admin');
    }

    public function logout()
    {
        (new AuthService())->logout();
        return redirect()->to('/admin/login');
    }
}
