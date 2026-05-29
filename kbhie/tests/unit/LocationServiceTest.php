<?php

namespace Tests\Unit;

use App\Libraries\LocationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class LocationServiceTest extends CIUnitTestCase
{
    public function testSetAndCurrent(): void
    {
        LocationService::set('Mumbai', 'Bandra', '400050');
        $cur = LocationService::current();
        $this->assertIsArray($cur);
        $this->assertSame('Mumbai',  $cur['city']);
        $this->assertSame('Bandra',  $cur['locality']);
        $this->assertSame('400050',  $cur['pincode']);
    }

    public function testLabelFormat(): void
    {
        LocationService::set('Mumbai', 'Bandra');
        $this->assertSame('Bandra, Mumbai', LocationService::label());

        LocationService::set('Delhi');
        $this->assertSame('Delhi', LocationService::label());
    }

    public function testClearRemovesLocation(): void
    {
        LocationService::set('Pune');
        LocationService::clear();
        $this->assertNull(LocationService::current());
    }
}
