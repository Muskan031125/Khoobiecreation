<?php

namespace App\Modules\Tracking\Controllers;

use App\Libraries\Tracking\TrackingService;
use CodeIgniter\Controller;

class EventController extends Controller
{
    public function ingest()
    {
        $raw = $this->request->getBody();
        $data = json_decode((string) $raw, true);
        if (! is_array($data)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid JSON']);
        }
        $tracking = new TrackingService();
        $id = $tracking->captureEvent($data);
        return $this->response->setJSON(['ok' => true, 'id' => $id]);
    }

    /** 1x1 tracking pixel for email open tracking. */
    public function emailPixel(string $token)
    {
        $tracking = new TrackingService();
        $tracking->captureEvent([
            'event_name' => 'EmailOpen',
            'event_id'   => 'email_' . $token,
            'source'     => 'webhook',
            'custom_data'=> ['token' => $token],
        ]);
        $this->response->setHeader('Content-Type', 'image/gif');
        $this->response->setHeader('Cache-Control', 'no-store');
        return $this->response->setBody(base64_decode('R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='));
    }
}
