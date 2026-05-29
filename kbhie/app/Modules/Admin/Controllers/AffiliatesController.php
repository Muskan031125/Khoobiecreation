<?php
namespace App\Modules\Admin\Controllers;

class AffiliatesController extends GenericController
{
    protected string $table = 'affiliates';
    protected string $title = 'Affiliates';
    protected array $listColumns = ['id','code','name','email','commission_pct','status','balance','lifetime_earnings'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['name','email','code'];
}
