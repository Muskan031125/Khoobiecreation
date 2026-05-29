<?php

namespace App\Modules\Api\Controllers;

use Config\Database;

class AuthController extends BaseApiController
{
    public function login()
    {
        $email = trim((string) $this->request->getJsonVar('email') ?: $this->request->getPost('email'));
        $pass  = (string) ($this->request->getJsonVar('password') ?: $this->request->getPost('password'));
        if (! $email || ! $pass) return $this->fail('Email + password required', 422);

        $db = Database::connect();
        $u  = $db->table('users')->where('email', $email)->get()->getRow();
        if (! $u || ! password_verify($pass, $u->password_hash ?? '')) {
            return $this->fail('Invalid credentials', 401);
        }

        // Issue a bearer token (kept hashed in DB)
        $token = bin2hex(random_bytes(24));
        $db->table('api_tokens')->insert([
            'user_id'    => $u->id,
            'token'      => hash('sha256', $token),
            'name'       => 'mobile-app',
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days')),
        ]);
        return $this->ok([
            'token' => $token,
            'user'  => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone],
            'expires_in_days' => 90,
        ]);
    }

    public function me()
    {
        $u = $this->requireAuth();
        return $this->ok(['user' => $u]);
    }

    public function logout()
    {
        $auth = $this->request->getHeaderLine('Authorization');
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            $token = trim(substr($auth, 7));
            Database::connect()->table('api_tokens')->where('token', hash('sha256', $token))->delete();
        }
        return $this->ok([]);
    }
}
