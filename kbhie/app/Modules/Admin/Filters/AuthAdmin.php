<?php

namespace App\Modules\Admin\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthAdmin implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session('user');
        $allowed = ['admin', 'super_admin', 'staff'];
        $roles = $user['roles'] ?? [];
        if (! $user || empty($user['id']) || ! array_intersect($allowed, $roles)) {
            return redirect()->to('/admin/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
