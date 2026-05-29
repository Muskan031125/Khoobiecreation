<?php
namespace App\Modules\Admin\Controllers;

class WarehousesController extends GenericController
{
    protected string $table = 'warehouses';
    protected string $title = 'Warehouses';
    protected array $listColumns = ['id','code','name','type','partner_id','is_default','is_active'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['name','code'];
}
