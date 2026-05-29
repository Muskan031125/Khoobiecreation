<?php
namespace App\Modules\Admin\Controllers;

class SubscriptionsController extends GenericController
{
    protected string $table = 'subscriptions';
    protected string $title = 'Active Subscriptions';
    protected array $listColumns = ['id','user_id','plan_id','status','next_billing_at','billing_cycles_completed'];
    protected array $sortableColumns = [];
    protected array $searchColumns = [];
}
