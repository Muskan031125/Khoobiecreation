<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('track', ['namespace' => 'App\Modules\Tracking\Controllers'], static function ($routes) {
    // Server-side event ingestion endpoint (called from JS for CAPI mirroring)
    $routes->post('event', 'EventController::ingest');
    // 1x1 pixel for email open tracking
    $routes->get('pixel/(:any)', 'EventController::emailPixel/$1');
});
