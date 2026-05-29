<?php
namespace App\Modules\Admin\Controllers;

class PromotionsController extends GenericController
{
    protected string $table = 'promotions';
    protected string $title = 'Promotions';
    protected array $listColumns = ['id','name','type','scope','auto_apply','is_active','priority'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['name'];
}
