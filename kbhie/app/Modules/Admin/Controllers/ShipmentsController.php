<?php
namespace App\Modules\Admin\Controllers;

class ShipmentsController extends GenericController
{
    protected string $table = 'shipments';
    protected string $title = 'Shipments';
    protected array $listColumns = ['id','order_id','partner_id','courier','awb','status','shipped_at','delivered_at'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['awb'];
}
