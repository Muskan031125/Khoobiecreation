<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Lightweight per-IP rate limiter using CI4's throttler.
 * Apply via route filter: `'filter' => 'rateLimit:10,60'` → max 10 hits per 60s.
 */
class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $limit = isset($arguments[0]) ? (int) $arguments[0] : 30;
        $secs  = isset($arguments[1]) ? (int) $arguments[1] : 60;

        $throttler = Services::throttler();
        $key = 'rl_' . md5($request->getIPAddress() . '|' . $request->getUri()->getPath());
        if (! $throttler->check($key, $limit, $secs)) {
            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $secs)
                ->setJSON(['ok' => false, 'error' => 'Too many requests, please slow down.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
