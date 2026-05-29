<?php

namespace App\Modules\Admin\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseAdminController extends Controller
{
    protected $helpers = ['form', 'url', 'text', 'number'];
    protected array $data = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->data = [
            'page' => ['title' => 'Khoobie Admin'],
            'user' => session('user'),
        ];
    }

    protected function view(string $template, array $data = []): string
    {
        return view($template, array_merge($this->data, $data));
    }
}
