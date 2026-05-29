<?php
namespace App\Modules\Admin\Controllers;

class PartnersController extends GenericController
{
    protected string $table = 'partners';
    protected string $title = 'Partners / Vendors';
    protected array $listColumns = ['id','company_name','contact_name','email','phone','status','fulfillment_type'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['company_name','contact_name','email'];
}
