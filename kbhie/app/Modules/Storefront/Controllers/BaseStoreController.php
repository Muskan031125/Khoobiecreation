<?php

namespace App\Modules\Storefront\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseStoreController extends Controller
{
    protected $helpers = ['form', 'url', 'text'];

    protected array $data = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data = [
            'brand'       => [
                'name'      => env('khoobie.brand_name',    'Khoobie Creations'),
                'short'     => env('khoobie.brand_short',   'Khoobie'),
                'tagline'   => env('khoobie.brand_tagline', 'Bringing unique handmade crafts and creative treasures to your doorstep.'),
                'domain'    => env('khoobie.brand_domain',  'khoobie.com'),
                'email'     => env('khoobie.support_email', ''),
                'phone'     => env('khoobie.support_phone', ''),
                'whatsapp'  => env('khoobie.support_whatsapp', ''),
            ],
            'tracking'    => [
                'gtm'   => env('tracking.gtm_container_id', ''),
                'ga4'   => env('tracking.ga4_measurement_id', ''),
                'meta'  => env('tracking.meta_pixel_id', ''),
            ],
            'page'        => [
                'title'       => 'Krafty Khoobie — Screen-free fun for kids',
                'description' => 'Board games, books, experiments and project kits that keep children off screens and learning every day.',
                'image'       => base_url('assets/og-default.jpg'),
                'url'         => current_url(),
                'type'        => 'website',
            ],
            'cart_count'    => session('cart_count') ?? 0,
            'user'          => session('user'),
            // [variant_id => qty] map — lets every product card render its in-cart state on first paint
            'cartVariants'  => (new \App\Libraries\Cart\CartService())->variantQtys(),
            // Shortlist + Compare — IDs so cards show the right toggle state on first paint
            'shortlistIds'  => (new \App\Libraries\ShortlistService())->ids(),
            'compareIds'    => (new \App\Libraries\CompareService())->ids(),
            'compareMax'    => \App\Libraries\CompareService::MAX,
            // Membership state — drives "Insider price" labels + auto-discount
            'membership'    => \App\Libraries\MembershipService::current(),
            // Sticky location — drives "Near you in {city}" sections, meetup auto-filter, concierge city context
            'location'      => \App\Libraries\LocationService::current(),
            'location_label'=> \App\Libraries\LocationService::label(),
            'all_cities'    => \App\Libraries\LocationService::availableCities(),
        ];
    }

    protected function view(string $template, array $data = [], array $options = []): string
    {
        $merged = array_merge($this->data, $data);
        return view($template, $merged, $options);
    }
}
