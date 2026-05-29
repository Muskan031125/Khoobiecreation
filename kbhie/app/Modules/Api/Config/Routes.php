<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

// Public API + webhook endpoints (CSRF disabled for these via filter config)
$routes->group('api', ['namespace' => 'App\Modules\Api\Controllers'], static function ($routes) {
    // Payment webhooks
    $routes->post('webhooks/razorpay', 'WebhookController::razorpay');
    $routes->post('webhooks/phonepe', 'WebhookController::phonepe');
    $routes->post('webhooks/shiprocket', 'WebhookController::shiprocket');
    $routes->post('webhooks/whatsapp', 'WebhookController::whatsapp');

    // Payment callbacks
    $routes->post('payment/razorpay/verify', 'PaymentController::razorpayVerify');
    $routes->post('payment/phonepe/callback', 'PaymentController::phonepeCallback');

    // Public catalog API (for future mobile/instagram shop sync)
    $routes->get('v1/products', 'V1\ProductController::index');
    $routes->get('v1/products/(:any)', 'V1\ProductController::show/$1');
});

// Mobile App API v1 — token-auth, JSON-only
$routes->group('api/v1', ['namespace' => 'App\Modules\Api\Controllers'], static function ($routes) {
    $routes->options('(:any)', static function () {
        return service('response')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With')
            ->setStatusCode(204);
    });
    // Auth
    $routes->post('auth/login',  'AuthController::login');
    $routes->post('auth/logout', 'AuthController::logout');
    $routes->get( 'auth/me',     'AuthController::me');
    // Catalog (public)
    $routes->get( 'app/products',       'ProductsController::index');
    $routes->get( 'app/products/(:any)','ProductsController::show/$1');
    $routes->get( 'app/categories',     'ProductsController::categories');

    // Cart (works for anon via session cookie or token-auth user)
    $routes->get( 'app/cart',           'CartController::get');
    $routes->post('app/cart/add',       'CartController::add');
    $routes->post('app/cart/set-qty',   'CartController::setQty');
    $routes->post('app/cart/coupon',    'CartController::applyCoupon');

    // Account (token-auth required)
    $routes->get( 'app/account/orders',          'AccountController::orders');
    $routes->get( 'app/account/orders/(:num)',   'AccountController::orderDetail/$1');
    $routes->get( 'app/account/downloads',       'AccountController::downloads');

    // Intent capture (trial / RSVP / seat-block — same as web)
    $routes->post('app/intent/capture',          'AccountController::intentCapture');
    $routes->post('app/intent/verify',           'AccountController::intentVerify');
});
