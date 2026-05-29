<?php

namespace App\Modules\Api\Controllers;

use App\Libraries\Payments\PhonePeService;
use App\Libraries\Payments\RazorpayService;
use CodeIgniter\Controller;
use Config\Database;

class PaymentController extends Controller
{
    /** Called by Razorpay checkout.js after successful payment to verify signature server-side. */
    public function razorpayVerify()
    {
        $orderId   = (int) $this->request->getPost('order_id');
        $rzpOrder  = (string) $this->request->getPost('razorpay_order_id');
        $rzpPay    = (string) $this->request->getPost('razorpay_payment_id');
        $signature = (string) $this->request->getPost('razorpay_signature');

        $rzp = new RazorpayService();
        if (! $rzp->verifyCheckoutSignature($rzpOrder, $rzpPay, $signature)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Signature mismatch']);
        }
        $rzp->recordSuccessfulPayment($orderId, $rzpOrder, $rzpPay);

        $orderNumber = Database::connect()->table('orders')->where('id', $orderId)->get()->getRow()->order_number ?? null;
        return $this->response->setJSON([
            'ok' => true,
            'redirect' => base_url('checkout/thank-you/' . $orderNumber),
        ]);
    }

    /** PhonePe callback (form post from gateway). */
    public function phonepeCallback()
    {
        $base64Body = (string) $this->request->getPost('response');
        $xVerify    = $this->request->getHeaderLine('X-VERIFY');
        $pp = new PhonePeService();
        if (! $pp->verifyCallback($base64Body, $xVerify)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid signature']);
        }
        $decoded = json_decode((string) base64_decode($base64Body), true) ?? [];
        $pp->handleCallback($decoded);

        $merchantTxn = $decoded['data']['merchantTransactionId'] ?? null;
        $payment = Database::connect()->table('payments')->where('gateway_order_id', $merchantTxn)->get()->getRowArray();
        $orderNumber = $payment ? Database::connect()->table('orders')->where('id', $payment['order_id'])->get()->getRow()->order_number : null;
        return redirect()->to(base_url('checkout/thank-you/' . $orderNumber));
    }
}
