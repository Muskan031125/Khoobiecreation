<?php
namespace App\Modules\Admin\Controllers;

class SubscribersController extends GenericController
{
    protected string $table = 'subscribers';
    protected string $title = 'Subscribers';
    protected array $listColumns = ['id','email','phone','name','source','consent_email','consent_whatsapp','created_at'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['email','phone','name'];
}
