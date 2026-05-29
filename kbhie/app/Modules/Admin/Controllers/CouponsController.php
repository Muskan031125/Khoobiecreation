<?php
namespace App\Modules\Admin\Controllers;

class CouponsController extends GenericController
{
    protected string $table = 'coupons';
    protected string $title = 'Coupons';
    protected array $listColumns = ['id','code','promotion_id','used_count','max_uses','is_active','ends_at'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['code'];
}
