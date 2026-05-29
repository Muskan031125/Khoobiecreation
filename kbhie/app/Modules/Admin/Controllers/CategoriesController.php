<?php
namespace App\Modules\Admin\Controllers;

class CategoriesController extends GenericController
{
    protected string $table = 'categories';
    protected string $title = 'Categories';
    protected array $listColumns = ['id','slug','name','parent_id','sort_order','is_active'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['name','slug'];
    protected string $orderBy = 'sort_order';
    protected string $orderDir = 'ASC';
}
