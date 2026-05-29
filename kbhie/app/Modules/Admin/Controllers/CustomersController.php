<?php
namespace App\Modules\Admin\Controllers;

class CustomersController extends GenericController
{
    protected string $table = 'users';
    protected string $title = 'Customers';
    protected array $listColumns = ['id','name','email','phone','status','last_login_at','created_at'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['name','email','phone'];
}
