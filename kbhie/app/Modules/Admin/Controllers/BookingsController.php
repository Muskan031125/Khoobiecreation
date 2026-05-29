<?php
namespace App\Modules\Admin\Controllers;

class BookingsController extends GenericController
{
    protected string $table = 'event_bookings';
    protected string $title = 'Event Bookings';
    protected array $listColumns = ['id','session_id','user_id','attendee_name','parent_phone','status','created_at'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['attendee_name','parent_phone'];
}
