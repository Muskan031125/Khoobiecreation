<?php

namespace App\Modules\Storefront\Controllers;

use Config\Database;

class PartnerSignupController extends BaseStoreController
{
    public function index()
    {
        return $this->view('App\Modules\Storefront\Views\partner_signup', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'Sell with Khoobie — list your products to lakhs of Indian parents',
                'description' => 'Join Khoobie as a brand partner or instructor. List your products, classes, or workshops. We bring the customers, you do what you love.',
            ]),
        ]);
    }

    public function submit()
    {
        $rules = [
            'company_name' => 'required|min_length[2]|max_length[200]',
            'contact_name' => 'required|min_length[2]|max_length[150]',
            'email'        => 'required|valid_email',
            'phone'        => 'required|min_length[10]|max_length[20]',
            'kind'         => 'required|in_list[brand,instructor,studio,creator]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = Database::connect();
        $db->table('partners')->insert([
            'company_name'    => $this->request->getPost('company_name'),
            'contact_name'    => $this->request->getPost('contact_name'),
            'email'           => $this->request->getPost('email'),
            'phone'           => $this->request->getPost('phone'),
            'city'            => $this->request->getPost('city'),
            'fulfillment_type'=> $this->request->getPost('fulfillment_type') ?: 'drop_ship',
            'commission_pct'  => 15.00,
            // Note: most partner tables also have status/approved flags - if present they'll need approval workflow
        ]);

        // Notify ops to review
        try {
            (new \App\Libraries\Notifications\NotificationService())->send(
                'email',
                env('khoobie.support_email', 'ops@khoobie.com'),
                'admin.new_partner_application',
                [
                    'company'  => $this->request->getPost('company_name'),
                    'contact'  => $this->request->getPost('contact_name'),
                    'phone'    => $this->request->getPost('phone'),
                    'email'    => $this->request->getPost('email'),
                    'kind'     => $this->request->getPost('kind'),
                    'message'  => $this->request->getPost('message') ?? '',
                ]
            );
        } catch (\Throwable $e) {}

        return redirect()->to('/sell-with-khoobie?submitted=1');
    }
}
