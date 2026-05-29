<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

// Unauthenticated admin routes
$routes->group('admin', ['namespace' => 'App\Modules\Admin\Controllers'], static function ($routes) {
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::loginPost');
});

$routes->group('admin', [
    'namespace' => 'App\Modules\Admin\Controllers',
    'filter'    => 'authAdmin',
], static function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('logout', 'AuthController::logout');

    // Catalog
    $routes->resource('products', ['controller' => 'ProductsController']);
    $routes->resource('categories', ['controller' => 'CategoriesController']);
    $routes->resource('variants', ['controller' => 'VariantsController']);
    $routes->resource('bundles', ['controller' => 'BundlesController']);

    // Orders & Fulfillment
    $routes->resource('orders', ['controller' => 'OrdersController']);
    $routes->post('orders/(:num)/confirm',         'OrdersController::confirm/$1');
    $routes->post('orders/(:num)/cancel',          'OrdersController::cancel/$1');
    $routes->post('orders/(:num)/capture-balance', 'OrdersController::captureBalance/$1');
    $routes->resource('shipments', ['controller' => 'ShipmentsController']);
    $routes->get( 'returns',                'ReturnsController::index');
    $routes->get( 'returns/(:num)',         'ReturnsController::show/$1');
    $routes->post('returns/(:num)/approve', 'ReturnsController::approve/$1');
    $routes->post('returns/(:num)/reject',  'ReturnsController::reject/$1');
    $routes->post('returns/(:num)/refunded','ReturnsController::markRefunded/$1');

    // Customers & Leads
    $routes->resource('customers', ['controller' => 'CustomersController']);
    $routes->get( 'leads',                     'LeadsController::index');
    $routes->get( 'leads/(:num)',              'LeadsController::show/$1');
    $routes->post('leads/(:num)/status',       'LeadsController::setStatus/$1');

    // Marketing
    $routes->resource('coupons', ['controller' => 'CouponsController']);
    $routes->resource('promotions', ['controller' => 'PromotionsController']);
    $routes->resource('gift-cards', ['controller' => 'GiftCardsController']);
    $routes->resource('loyalty-rules', ['controller' => 'LoyaltyRulesController']);
    $routes->resource('subscribers', ['controller' => 'SubscribersController']);
    $routes->get( 'campaigns',                  'CampaignsController::index');
    $routes->get( 'campaigns/new',              'CampaignsController::new');
    $routes->get( 'campaigns/(:num)/edit',      'CampaignsController::edit/$1');
    $routes->post('campaigns/save',             'CampaignsController::save');
    $routes->post('campaigns/ai-draft',         'CampaignsController::aiDraft');
    $routes->post('campaigns/(:num)/send',      'CampaignsController::send/$1');

    // Partners
    $routes->resource('partners', ['controller' => 'PartnersController']);
    $routes->resource('payouts', ['controller' => 'PayoutsController']);

    // Affiliates
    $routes->resource('affiliates', ['controller' => 'AffiliatesController']);

    // Subscriptions
    $routes->resource('subscription-plans', ['controller' => 'SubscriptionPlansController']);
    $routes->resource('subscriptions', ['controller' => 'SubscriptionsController']);

    // Events / Bookings
    $routes->resource('events', ['controller' => 'EventsController']);
    $routes->resource('bookings', ['controller' => 'BookingsController']);

    // Inventory
    $routes->resource('inventory', ['controller' => 'InventoryController']);
    $routes->resource('warehouses', ['controller' => 'WarehousesController']);

    // Reports
    $routes->get('reports', 'ReportsController::index');
    $routes->get('reports/sales', 'ReportsController::sales');
    $routes->get('reports/products', 'ReportsController::products');
    $routes->get('reports/funnel', 'ReportsController::funnel');

    // GST & Invoices
    $routes->resource('invoices', ['controller' => 'InvoicesController']);
    $routes->get('invoices/(:num)/pdf', 'InvoicesController::pdf/$1');

    // Settings
    $routes->get('settings', 'SettingsController::index');
    $routes->post('settings', 'SettingsController::save');

    // URL Importer (LLM-powered)
    $routes->get( 'import',          'ImportController::index');
    $routes->post('import/fetch',    'ImportController::fetch');
    $routes->post('import/save',     'ImportController::save');

    // Product file upload (for digital products)
    $routes->post('product-files/(:num)/upload', 'ProductFilesController::upload/$1');
    $routes->post('product-files/(:num)/delete', 'ProductFilesController::delete/$1');

    // Bulk actions on any list page
    $routes->post('bulk/(:any)',        'BulkController::execute/$1');
    $routes->get('bulk/(:any)/export',  'BulkController::export/$1');

    // Reviews moderation
    $routes->get( 'reviews',                   'ReviewsController::index');
    $routes->post('reviews/(:num)/approve',    'ReviewsController::approve/$1');
    $routes->post('reviews/(:num)/reject',     'ReviewsController::reject/$1');

    // Hyperlocal demand intelligence
    $routes->get('hyperlocal',                 'HyperlocalController::index');

    // Warehouse routing zones
    $routes->get( 'warehouse-zones',                'WarehouseZonesController::index');
    $routes->post('warehouse-zones/save',           'WarehouseZonesController::save');
    $routes->post('warehouse-zones/(:num)/delete',  'WarehouseZonesController::delete/$1');
    $routes->get( 'warehouse-zones/test',           'WarehouseZonesController::test');

    // Investor demo
    $routes->get('investor', 'InvestorController::index');

    // AI content generators (called from product edit form)
    $routes->post('ai/description',  'AiController::description');
    $routes->post('ai/seo-meta',     'AiController::seoMeta');
    $routes->post('ai/alt-text',     'AiController::altText');
    $routes->post('ai/review-summary','AiController::reviewSummary');
    $routes->post('ai/blog-draft',   'AiController::blogDraft');

    // Blog admin
    $routes->resource('blogs', ['controller' => 'BlogsController']);
});
