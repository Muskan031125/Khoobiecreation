<?php

namespace App\Modules\Lead\Controllers;

use App\Libraries\Tracking\TrackingService;
use App\Models\LeadModel;
use App\Modules\Storefront\Controllers\BaseStoreController;
use Config\Database;

class LeadController extends BaseStoreController
{
    /** Generic capture endpoint (used by exit-intent, header signup, etc) */
    public function capture()
    {
        return $this->saveLead('generic');
    }

    public function newsletter()
    {
        return $this->saveLead('newsletter');
    }

    public function raffle()
    {
        return $this->saveLead('raffle');
    }

    public function thankYou()
    {
        return $this->view('App\Modules\Lead\Views\thank_you', [
            'page' => array_merge($this->data['page'], ['title' => 'Thank you!']),
        ]);
    }

    protected function saveLead(string $source)
    {
        $leads = new LeadModel();
        $db = Database::connect();
        $req = $this->request;

        $data = [
            'email'        => $req->getPost('email')   ?: null,
            'phone'        => $req->getPost('phone')   ?: null,
            'name'         => $req->getPost('name')    ?: null,
            'city'         => $req->getPost('city')    ?: null,
            'pincode'      => $req->getPost('pincode') ?: null,
            'source'       => $source,
            'anon_id'      => $this->getAnonId(),
            'landing_url'  => session('landing_url') ?? null,
            'utm_source'   => $req->getCookie('kb_utm_source')   ?: $req->getGet('utm_source'),
            'utm_medium'   => $req->getCookie('kb_utm_medium')   ?: $req->getGet('utm_medium'),
            'utm_campaign' => $req->getCookie('kb_utm_campaign') ?: $req->getGet('utm_campaign'),
            'fbclid'       => $req->getCookie('kb_fbclid')       ?: $req->getGet('fbclid'),
            'ip'           => $req->getIPAddress(),
            'user_agent'   => substr((string) $req->getUserAgent(), 0, 500),
        ];

        if (empty($data['email']) && empty($data['phone'])) {
            $msg = 'Please share at least an email or a phone number.';
            return $this->wantsJson()
                ? $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => $msg])
                : redirect()->back()->with('error', $msg);
        }

        $lead = $leads->upsert($data);

        // Audit row
        $db->table('lead_form_submissions')->insert([
            'lead_id'    => $lead['id'],
            'form_key'   => $source,
            'payload'    => json_encode($req->getPost()),
            'ip'         => $data['ip'],
            'user_agent' => $data['user_agent'],
        ]);

        // Stash prefill for later signup/checkout
        session()->set('lead_prefill', [
            'name'  => $lead['name'],
            'email' => $lead['email'],
            'phone' => $lead['phone'],
        ]);
        session()->set('lead_id', $lead['id']);

        // Fire Lead event server-side (mirrors what JS does on success)
        (new TrackingService())->captureEvent([
            'event_name'  => 'Lead',
            'anon_id'     => $data['anon_id'],
            'email'       => $lead['email'],
            'phone'       => $lead['phone'],
            'url'         => current_url(),
            'source'      => 'server',
            'custom_data' => ['lead_source' => $source],
        ]);

        // Subscribe to newsletter list (de-duped by unique email/phone)
        if ($lead['email'] || $lead['phone']) {
            $existing = $db->table('subscribers')
                ->groupStart()
                    ->where('email', $lead['email'])
                    ->orWhere('phone', $lead['phone'])
                ->groupEnd()
                ->countAllResults();
            if (! $existing) {
                $db->table('subscribers')->insert([
                    'email'  => $lead['email'],
                    'phone'  => $lead['phone'],
                    'name'   => $lead['name'],
                    'source' => $source,
                    'tags'   => json_encode([$source]),
                    'consent_email'    => $lead['email'] ? 1 : 0,
                    'consent_whatsapp' => $lead['phone'] ? 1 : 0,
                ]);
            }
        }

        if ($this->wantsJson()) {
            return $this->response->setJSON([
                'ok' => true,
                'message' => 'Got it! Check your inbox / WhatsApp for the offer.',
                'reward_message' => 'Use code WELCOME10 at checkout for 10% off.',
            ]);
        }
        return redirect()->to('/lead/thank-you')->with('success', 'You\'re in! Check your inbox and WhatsApp shortly.');
    }

    protected function wantsJson(): bool
    {
        $accept = (string) $this->request->getHeaderLine('Accept');
        return $this->request->isAJAX() || str_contains($accept, 'application/json');
    }

    protected function getAnonId(): ?string
    {
        $cookie = $this->request->getCookie('kb_anon');
        return $cookie ?: null;
    }
}
