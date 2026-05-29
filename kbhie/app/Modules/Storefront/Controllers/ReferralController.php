<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\ReferralService;

class ReferralController extends BaseStoreController
{
    /** /r/{code} — referral landing. Sets cookie, attributes, redirects. */
    public function land(string $code)
    {
        $utm = [
            'source'   => $this->request->getGet('utm_source'),
            'medium'   => $this->request->getGet('utm_medium'),
            'campaign' => $this->request->getGet('utm_campaign'),
            'channel'  => $this->request->getGet('via'),
        ];
        $res = (new ReferralService())->landByCode($code, $utm);

        // Where to go: explicit ?to= override, else home
        $to = $this->request->getGet('to');
        $target = $to ? ltrim($to, '/') : '';

        if ($res['ok']) {
            session()->setFlashdata('referral_welcome', "🎉 {$res['referrer']['name']} sent you here! Use code WELCOME10 for 10% off your first order.");
        }
        return redirect()->to(base_url($target));
    }

    /** /account/referrals — the parent's referral dashboard widget. */
    public function dashboard()
    {
        $user = session('user');
        if (! $user) return redirect()->to('/login');

        $data = (new ReferralService())->dashboard((int) $user['id']);

        return $this->view('App\Modules\Storefront\Views\referrals_dashboard', [
            'page' => array_merge($this->data['page'], ['title' => 'Refer a Friend — Krafty Khoobie']),
            'ref'  => $data,
        ]);
    }
}
