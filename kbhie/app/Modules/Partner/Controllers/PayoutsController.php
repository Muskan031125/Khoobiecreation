<?php

namespace App\Modules\Partner\Controllers;

use Config\Database;

class PayoutsController extends BasePartnerController
{
    public function index()
    {
        if (! $this->partner) return redirect()->to('/partner/login');
        $rows = Database::connect()->table('partner_payouts')
            ->where('partner_id', $this->partner['id'])
            ->orderBy('id', 'DESC')->get()->getResultArray();
        return $this->view('App\Modules\Partner\Views\payouts_index', [
            'page' => ['title' => 'Payouts'], 'rows' => $rows,
        ]);
    }
}
