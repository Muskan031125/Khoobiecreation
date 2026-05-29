<?php
namespace App\Modules\Admin\Controllers;

class SubscriptionPlansController extends GenericController
{
    protected string $table = 'subscription_plans';
    protected string $title = 'Subscription Plans';
    protected array $listColumns = ['id','name','slug','billing_interval','interval_count','price','is_active'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['name','slug'];
}
