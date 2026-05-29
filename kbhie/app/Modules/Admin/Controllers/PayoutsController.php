<?php
namespace App\Modules\Admin\Controllers;

class PayoutsController extends GenericController
{
    protected string $table = 'partner_payouts';
    protected string $title = 'Partner Payouts';
    protected array $listColumns = ['id','partner_id','period_start','period_end','gross_amount','commission','net_payable','status','paid_at'];
    protected array $sortableColumns = [];
    protected array $searchColumns = [];
}
