<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'App\Modules\Storefront\Controllers'], static function ($routes) {
    $routes->get('/shop', 'CatalogController::index');
    $routes->get('search', 'CatalogController::index');
    $routes->get('shop/(:any)', 'CatalogController::category/$1');
    $routes->get('product/(:any)',           'ProductController::show/$1');
    $routes->get('go/(:any)',                'AffiliateController::go/$1');
    $routes->post('product/(:any)/review',   'ProductController::reviewSubmit/$1');
    $routes->post('product/(:any)/question', 'ProductController::questionSubmit/$1');
    $routes->get('cart', 'CartController::index');
    $routes->post('cart/add', 'CartController::add');
    $routes->post('cart/update', 'CartController::update');
    $routes->post('cart/remove', 'CartController::remove');
    $routes->get('cart/mini',     'CartController::mini');
    $routes->get('cart/count',    'CartController::count');
    $routes->post('cart/set-qty', 'CartController::setQty');
    $routes->post('cart/apply-coupon',  'CartController::applyCoupon');
    $routes->post('cart/remove-coupon', 'CartController::removeCoupon');

    // Shortlist (wishlist) — session-backed
    $routes->get( 'shortlist',         'ShortlistController::index');
    $routes->post('shortlist/toggle',  'ShortlistController::toggle');
    $routes->post('shortlist/remove',  'ShortlistController::remove');

    // Compare — session-backed, capped at 4 products
    $routes->get( 'compare',           'CompareController::index');
    $routes->post('compare/toggle',    'CompareController::toggle');
    $routes->post('compare/remove',    'CompareController::remove');
    $routes->post('compare/clear',     'CompareController::clear');

    // Recently-viewed full history page
    $routes->get( 'recently-viewed',        'RecentlyViewedController::index');
    $routes->post('recently-viewed/clear',  'RecentlyViewedController::clear');

    // Universal intent capture (trial / RSVP / seat-block / discovery call / notify-me)
    $routes->post('intent/capture',         'IntentController::capture');
    $routes->post('intent/verify',          'IntentController::verify');
    $routes->post('intent/resend',          'IntentController::resend');
    $routes->post('intent/pay',             'IntentController::pay');

    // SEO / AEO / GEO / PPC feeds
    $routes->get('sitemap.xml',                 'SeoController::sitemap');
    $routes->get('robots.txt',                  'SeoController::robots');
    $routes->get('llms.txt',                    'SeoController::llms');
    $routes->get('llms-full.txt',               'SeoController::llmsFull');
    $routes->get('feed/google-merchant.xml',    'SeoController::googleMerchant');
    $routes->get('feed/meta-catalog.csv',       'SeoController::metaCatalog');
    $routes->get('feed/products.json',          'SeoController::productsJson');

    // Referral landing: /r/{code} sets cookie then redirects to home
    $routes->get('r/(:any)',                    'ReferralController::land/$1');
    $routes->get('account/referrals',           'ReferralController::dashboard');

    // AI Concierge — LLM-powered product recommender (powers the floating chat widget)
    $routes->post('concierge/ask',              'ConciergeController::ask');

    // Public blog (admin-driven, AI-drafted)
    $routes->get('blog',                        'BlogController::index');
    $routes->get('blog/(:any)',                 'BlogController::show/$1');

    // Dynamic Open Graph share images
    $routes->get('og/product/(:any)',           'OgImageController::product/$1');

    // Bundles (kit + class flywheel)
    $routes->get( 'bundle/(:any)',              'BundleController::show/$1');
    $routes->post('bundle/(:any)/add',          'BundleController::addToCart/$1');

    // Per-vertical SEO landing pages
    $routes->get('classes',                     'VerticalController::classes');
    $routes->get('meetups',                     'VerticalController::meetups');
    $routes->get('digital',                     'VerticalController::digital');
    $routes->get('affiliate',                   'VerticalController::affiliate');

    // Digital download token endpoint
    $routes->get('download/(:any)',             'DownloadController::get/$1');
    // Direct file serving (gated by DownloadController in production via signed URLs)
    $routes->get('files/(:num)/(:any)',         'FilesController::get/$1/$2');

    // Sticky location selector
    $routes->post('location/set',               'LocationController::set');
    $routes->post('location/clear',             'LocationController::clear');

    // Web push subscription
    $routes->post('push/subscribe',             'PushController::subscribe');

    // Express single-item enrolment (classes / events / services / memberships)
    $routes->get( 'enrol/(:num)',               'EnrolController::show/$1');
    $routes->post('enrol/(:num)/pay',           'EnrolController::pay/$1');
    $routes->get( 'enrol/confirmed/(:any)',     'EnrolController::confirmed/$1');

    // "Sell with Khoobie" — partner signup
    $routes->get( 'sell-with-khoobie',          'PartnerSignupController::index');
    $routes->post('sell-with-khoobie',          'PartnerSignupController::submit');
    $routes->get('checkout', 'CheckoutController::index');
    $routes->post('checkout/place', 'CheckoutController::place');
    $routes->get('checkout/thank-you/(:any)', 'CheckoutController::thankYou/$1');
});
