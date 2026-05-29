<?php
namespace App\Modules\Admin\Controllers;

class VariantsController extends GenericController
{
    protected string $table = 'product_variants';
    protected string $title = 'Variants';
    protected array $listColumns = ['id','product_id','sku','name','price','is_default','is_active'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['sku','name'];
}
