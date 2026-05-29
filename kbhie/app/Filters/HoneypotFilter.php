<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Reject any POST/PUT/PATCH request where the hidden "website" honeypot field
 * is filled in. Real users never see it; bots almost always do.
 *
 * Add `<input type="text" name="website" tabindex="-1" autocomplete="off"
 *  style="position:absolute;left:-9999px" aria-hidden="true">` to your forms.
 */
class HoneypotFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! in_array($request->getMethod(true), ['POST', 'PUT', 'PATCH'], true)) return;

        $field = 'website';
        if ($request->getPost($field)) {
            log_message('warning', 'Honeypot tripped: ip=' . $request->getIPAddress() . ' ua=' . substr((string) $request->getUserAgent(), 0, 100));
            return service('response')->setStatusCode(429)->setJSON(['ok' => false, 'error' => 'Request blocked.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
