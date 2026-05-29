<?php
namespace App\Modules\Admin\Controllers;

use Config\Database;

class InvoicesController extends GenericController
{
    protected string $table = 'invoices';
    protected string $title = 'GST Invoices';
    protected array $listColumns = ['id','invoice_number','invoice_type','order_id','invoice_date','total_amount','is_cancelled'];
    protected array $sortableColumns = [];
    protected array $searchColumns = ['invoice_number'];

    public function pdf($id)
    {
        $invoice = Database::connect()->table('invoices')->where('id', $id)->get()->getRowArray();
        if (! $invoice) return redirect()->to('/admin/invoices');
        if ($invoice['pdf_path']) {
            $path = WRITEPATH . $invoice['pdf_path'];
            if (file_exists($path)) {
                return $this->response->download($path, null);
            }
        }
        return $this->view('App\Modules\Admin\Views\invoice_view', [
            'page' => ['title' => 'Invoice ' . $invoice['invoice_number']],
            'invoice' => $invoice,
        ]);
    }
}
