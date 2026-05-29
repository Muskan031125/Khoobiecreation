<?php
namespace App\Modules\Admin\Controllers;

class BundlesController extends GenericController
{
    protected string $table = 'bundle_items';
    protected string $title = 'Bundles';
    protected array $listColumns = ['id','bundle_product_id','child_product_id','quantity','discount_pct'];
    protected array $sortableColumns = [];
    protected array $searchColumns = [];
}
