<?php

namespace App\Modules\Partner\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthPartner implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session('user');
        if (! $user || empty($user['id']) || ! in_array('partner', $user['roles'] ?? [], true)) {
            return redirect()->to('/partner/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
