<?php

namespace App\Modules\Partner\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Config\Database;

abstract class BasePartnerController extends Controller
{
    protected $helpers = ['form', 'url', 'number'];
    protected array $data = [];
    protected ?array $partner = null;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $user = session('user');
        if ($user && in_array('partner', $user['roles'] ?? [], true)) {
            $row = Database::connect()->table('partner_users pu')
                ->join('partners p', 'p.id = pu.partner_id')
                ->where('pu.user_id', $user['id'])
                ->select('p.*, pu.role AS partner_role')
                ->get()->getRowArray();
            $this->partner = $row ?: null;
        }
        $this->data = [
            'page'    => ['title' => 'Partner Portal'],
            'user'    => $user,
            'partner' => $this->partner,
        ];
    }

    protected function view(string $template, array $data = []): string
    {
        return view($template, array_merge($this->data, $data));
    }
}
