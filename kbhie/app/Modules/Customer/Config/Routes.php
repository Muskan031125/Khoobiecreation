<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->get('login',  '\App\Modules\Customer\Controllers\AuthController::login');
$routes->post('login', '\App\Modules\Customer\Controllers\AuthController::loginPost');
$routes->get('signup', '\App\Modules\Customer\Controllers\AuthController::signup');
$routes->post('signup','\App\Modules\Customer\Controllers\AuthController::signupPost');
$routes->get('logout', '\App\Modules\Customer\Controllers\AuthController::logout');
$routes->post('otp/send',   '\App\Modules\Customer\Controllers\AuthController::sendOtp');
$routes->post('otp/verify', '\App\Modules\Customer\Controllers\AuthController::verifyOtp');

$routes->group('account', [
    'namespace' => 'App\Modules\Customer\Controllers',
    'filter'    => 'authCustomer',
], static function ($routes) {
    $routes->get('/', 'AccountController::index');
    // Verification flow + rewards
    $routes->post('verify/phone/send',    'VerifyController::sendPhone');
    $routes->post('verify/phone/confirm', 'VerifyController::confirmPhone');
    $routes->post('verify/email/send',    'VerifyController::sendEmail');
    $routes->post('verify/email/confirm', 'VerifyController::confirmEmail');

    $routes->get('orders', 'AccountController::orders');
    $routes->get('orders/(:num)', 'AccountController::orderDetail/$1');
    $routes->get('orders/(:num)/track', 'AccountController::trackOrder/$1');
    $routes->post('orders/(:num)/return', 'AccountController::requestReturn/$1');
    $routes->get('buy-again', 'AccountController::buyAgain');
    $routes->get('downloads', 'AccountController::downloads');
    $routes->get('addresses', 'AccountController::addresses');
    $routes->post('addresses/save', 'AccountController::addressSave');
    $routes->post('addresses/(:num)/delete', 'AccountController::addressDelete/$1');
    $routes->get('wallet', 'AccountController::wallet');
    $routes->post('subscriptions/(:num)/cancel', 'AccountController::subscriptionCancel/$1');
    $routes->post('subscriptions/(:num)/pause',  'AccountController::subscriptionPause/$1');
    $routes->post('subscriptions/(:num)/resume', 'AccountController::subscriptionResume/$1');
    $routes->get('wishlist', 'AccountController::wishlist');
    $routes->get('subscriptions', 'AccountController::subscriptions');
    $routes->get('referrals', 'AccountController::referrals');
    $routes->get('profile', 'AccountController::profile');
    $routes->post('profile/update', 'AccountController::profileUpdate');
});
