<?php

namespace App\Modules\Api\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseApiController extends Controller
{
    protected $helpers = ['url', 'text', 'form'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->response->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With');
    }

    protected function ok(array $data = [], int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON(['ok' => true] + $data);
    }
    protected function fail(string $msg, int $code = 400, array $extra = []): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON(['ok' => false, 'error' => $msg] + $extra);
    }

    /** Bearer token → user lookup; null if anon. */
    protected function authUser(): ?array
    {
        $auth = $this->request->getHeaderLine('Authorization');
        if (! $auth || ! str_starts_with($auth, 'Bearer ')) return null;
        $token = trim(substr($auth, 7));
        if (! $token) return null;
        $row = \Config\Database::connect()->table('api_tokens t')
            ->select('u.id, u.name, u.email, u.phone')
            ->join('users u', 'u.id = t.user_id')
            ->where('t.token', hash('sha256', $token))
            ->where('(t.expires_at IS NULL OR t.expires_at > NOW())', null, false)
            ->get()->getRowArray();
        return $row ?: null;
    }

    protected function requireAuth(): ?array
    {
        $u = $this->authUser();
        if (! $u) { $this->fail('Unauthorized', 401)->send(); exit; }
        return $u;
    }
}
