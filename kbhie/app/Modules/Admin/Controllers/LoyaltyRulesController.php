<?php
namespace App\Modules\Admin\Controllers;

class LoyaltyRulesController extends GenericController
{
    protected string $table = 'loyalty_rules';
    protected string $title = 'Loyalty Rules';
    protected array $listColumns = ['id','event','description','points_formula','expires_days','is_active'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['event','description'];
}
