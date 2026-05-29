<?php
namespace App\Modules\Admin\Controllers;

class GiftCardsController extends GenericController
{
    protected string $table = 'gift_cards';
    protected string $title = 'Gift Cards';
    protected array $listColumns = ['id','code','initial_value','balance','status','recipient_email','expires_at'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['code','recipient_email'];
}
