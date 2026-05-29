<?php

namespace App\Modules\Customer\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthCustomer implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session('user');
        if (! $user || empty($user['id']) || ! in_array('customer', $user['roles'] ?? [], true)) {
            return redirect()->to('/login?next=' . urlencode(current_url()));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
