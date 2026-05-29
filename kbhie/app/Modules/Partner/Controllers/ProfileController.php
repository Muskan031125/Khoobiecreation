<?php

namespace App\Modules\Partner\Controllers;

class ProfileController extends BasePartnerController
{
    public function index()
    {
        return $this->view('App\Modules\Partner\Views\profile', [
            'page' => ['title' => 'My Profile'],
        ]);
    }
}
