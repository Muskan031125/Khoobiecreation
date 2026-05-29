<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

// Unauthenticated partner routes
$routes->group('partner', ['namespace' => 'App\Modules\Partner\Controllers'], static function ($routes) {
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::loginPost');
});

$routes->group('partner', [
    'namespace' => 'App\Modules\Partner\Controllers',
    'filter'    => 'authPartner',
], static function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('logout', 'AuthController::logout');

    $routes->get('orders', 'OrdersController::index');
    $routes->get('orders/(:num)', 'OrdersController::detail/$1');
    $routes->post('orders/(:num)/ship', 'OrdersController::ship/$1');
    $routes->post('orders/(:num)/awb', 'OrdersController::updateAwb/$1');

    $routes->get('products',            'ProductsController::index');
    $routes->get('products/new',        'ProductsController::new');
    $routes->get('products/(:num)/edit','ProductsController::edit/$1');
    $routes->post('products/save',      'ProductsController::save');
    $routes->get('inventory', 'InventoryController::index');
    $routes->post('inventory/update', 'InventoryController::update');

    $routes->get('payouts', 'PayoutsController::index');
    $routes->get('profile', 'ProfileController::index');
});
