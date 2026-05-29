<?php
namespace App\Modules\Admin\Controllers;

class InventoryController extends GenericController
{
    protected string $table = 'inventory';
    protected string $title = 'Inventory';
    protected array $listColumns = ['id','variant_id','warehouse_id','qty_on_hand','qty_reserved','qty_incoming','reorder_level'];
    protected array $sortableColumns = [];
    protected array $searchColumns = [];
}
