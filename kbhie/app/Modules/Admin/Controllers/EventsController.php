<?php
namespace App\Modules\Admin\Controllers;

class EventsController extends GenericController
{
    protected string $table = 'events';
    protected string $title = 'Events / Workshops';
    protected array $listColumns = ['id','name','type','location_type','instructor_name','is_active'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['name'];
}
