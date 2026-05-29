<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Default landing — Storefront home
$routes->get('/', '\App\Modules\Storefront\Controllers\HomeController::index');

// Auto-load route files from each module
$moduleRoutes = glob(APPPATH . 'Modules/*/Config/Routes.php');
foreach ($moduleRoutes as $moduleRouteFile) {
    require $moduleRouteFile;
}
