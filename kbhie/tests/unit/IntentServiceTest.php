<?php

namespace Tests\Unit;

use App\Libraries\IntentService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class IntentServiceTest extends CIUnitTestCase
{
    public function testCaptureFailsWithoutContact(): void
    {
        $res = (new IntentService())->capture([
            'product_id' => 1,
            'kind'       => 'trial',
            'name'       => 'Test',
        ]);
        $this->assertFalse($res['ok']);
        $this->assertArrayHasKey('errors', $res);
    }

    public function testCaptureFailsWithBadKind(): void
    {
        $res = (new IntentService())->capture([
            'product_id' => 1,
            'kind'       => 'wat_is_this',
            'phone'      => '9876543210',
        ]);
        $this->assertFalse($res['ok']);
    }

    public function testVerifyOtpFailsForMissingIntent(): void
    {
        $res = (new IntentService())->verifyOtp(99999999, '123456');
        $this->assertFalse($res['ok']);
        $this->assertSame('Intent not found.', $res['error']);
    }
}
