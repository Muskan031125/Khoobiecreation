<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\IntentService;

class IntentController extends BaseStoreController
{
    public function capture()
    {
        $input = $this->request->getPost();
        $res = (new IntentService())->capture($input);
        return $this->response->setJSON($res);
    }

    public function verify()
    {
        $intentId = (int) $this->request->getPost('intent_id');
        $code     = (string) $this->request->getPost('otp');
        $res = (new IntentService())->verifyOtp($intentId, $code);
        return $this->response->setJSON($res);
    }

    public function resend()
    {
        $intentId = (int) $this->request->getPost('intent_id');
        $res = (new IntentService())->resendOtp($intentId);
        return $this->response->setJSON($res);
    }

    /**
     * Stub for part-payment confirmation. Real impl calls Razorpay/PhonePe;
     * here we accept a mock ref so the demo flow completes end-to-end.
     */
    public function pay()
    {
        $intentId = (int) $this->request->getPost('intent_id');
        $amount   = (int) $this->request->getPost('amount');
        $ref      = (string) $this->request->getPost('ref', 'MOCK-' . bin2hex(random_bytes(6)));
        $res = (new IntentService())->capturePartPayment($intentId, $amount, $ref);
        return $this->response->setJSON($res);
    }
}
