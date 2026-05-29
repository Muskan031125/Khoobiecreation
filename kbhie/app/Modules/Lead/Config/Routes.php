<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('lead', ['namespace' => 'App\Modules\Lead\Controllers'], static function ($routes) {
    $routes->post('capture', 'LeadController::capture');
    $routes->post('newsletter', 'LeadController::newsletter');
    $routes->post('raffle', 'LeadController::raffle');
    $routes->get('thank-you', 'LeadController::thankYou');
});
