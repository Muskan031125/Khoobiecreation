<?php

namespace Tests\Unit;

use App\Libraries\WarehouseRoutingService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class WarehouseRoutingTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;
    protected $refresh = false;

    public function testRoutesMumbaiPincodeToLocalWarehouse(): void
    {
        $route = WarehouseRoutingService::routeForPincode('400050');
        $this->assertIsArray($route);
        $this->assertArrayHasKey('warehouse_id', $route);
        $this->assertArrayHasKey('estimated_days', $route);
        $this->assertLessThanOrEqual(6, $route['estimated_days']);
    }

    public function testDefaultsForUnknownPincode(): void
    {
        $route = WarehouseRoutingService::routeForPincode('999999');
        $this->assertArrayHasKey('warehouse_id', $route);
    }

    public function testEmptyPincodeStillReturnsDefault(): void
    {
        $route = WarehouseRoutingService::routeForPincode('');
        $this->assertArrayHasKey('warehouse_id', $route);
        $this->assertNotEmpty($route['matched_zone']);
    }
}
