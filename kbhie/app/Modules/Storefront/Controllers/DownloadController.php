<?php

namespace App\Modules\Storefront\Controllers;

use App\Libraries\DigitalDeliveryService;

class DownloadController extends BaseStoreController
{
    /** /download/{token} — validates, increments counter, redirects to file URL. */
    public function get(string $token)
    {
        $svc = new DigitalDeliveryService();
        $res = $svc->validateAndConsume($token);

        if (! empty($res['error'])) {
            return $this->view('App\Modules\Storefront\Views\download_error', [
                'page'  => array_merge($this->data['page'], ['title' => 'Download — Khoobie']),
                'error' => $res['error'],
            ]);
        }

        // Redirect to the actual file URL — could also be a signed S3/CloudFront URL
        return redirect()->to($res['file_url']);
    }
}
