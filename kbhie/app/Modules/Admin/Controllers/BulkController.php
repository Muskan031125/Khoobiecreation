<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

/**
 * Bulk operations for any admin list page.
 *   POST /admin/bulk/{table}  with body: { action: '...', ids: [], ...args }
 *
 * Supports: products, orders, leads (intents), categories, customers
 */
class BulkController extends BaseAdminController
{
    public function execute($table)
    {
        $ids    = $this->request->getPost('ids') ?: [];
        $action = (string) $this->request->getPost('action');
        $ids    = array_filter(array_map('intval', is_array($ids) ? $ids : explode(',', $ids)));

        if (empty($ids) || ! $action) {
            return $this->fail('No items or action selected.');
        }
        $allowedTables = ['products','orders','intents','categories','customers','blogs'];
        if (! in_array($table, $allowedTables, true)) return $this->fail('Invalid table.');

        $db = Database::connect();
        $touched = 0;

        switch ("{$table}.{$action}") {
            case 'products.activate':
                $touched = $db->table('products')->whereIn('id', $ids)->update(['status' => 'active']);
                break;
            case 'products.deactivate':
                $touched = $db->table('products')->whereIn('id', $ids)->update(['status' => 'draft']);
                break;
            case 'products.delete':
                $touched = $db->table('products')->whereIn('id', $ids)->update(['deleted_at' => date('Y-m-d H:i:s')]);
                break;
            case 'products.feature':
                $touched = $db->table('products')->whereIn('id', $ids)->update(['is_featured' => 1]);
                break;
            case 'products.unfeature':
                $touched = $db->table('products')->whereIn('id', $ids)->update(['is_featured' => 0]);
                break;
            case 'products.change_price':
                $newPct = (float) $this->request->getPost('change_pct');
                if (! $newPct) return $this->fail('change_pct required');
                // Apply +/- percent to default variant price
                $db->query("UPDATE product_variants SET price = ROUND(price * (1 + ?/100)) WHERE product_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ") AND is_default = 1", array_merge([$newPct], $ids));
                $touched = count($ids);
                break;

            case 'orders.cancel':
                $touched = $db->table('orders')->whereIn('id', $ids)->update(['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')]);
                break;
            case 'orders.confirm':
                $touched = $db->table('orders')->whereIn('id', $ids)->update(['status' => 'processing', 'confirmation_status' => 'confirmed', 'confirmed_at' => date('Y-m-d H:i:s')]);
                break;

            case 'intents.contacted':
                $touched = $db->table('intents')->whereIn('id', $ids)->update(['status' => 'contacted']);
                break;
            case 'intents.cancelled':
                $touched = $db->table('intents')->whereIn('id', $ids)->update(['status' => 'cancelled']);
                break;

            case 'blogs.publish':
                $touched = $db->table('blogs')->whereIn('id', $ids)->update(['status' => 'published', 'published_at' => date('Y-m-d H:i:s')]);
                break;
            case 'blogs.archive':
                $touched = $db->table('blogs')->whereIn('id', $ids)->update(['status' => 'archived']);
                break;

            default:
                return $this->fail('Unsupported action: ' . $action);
        }

        return $this->response->setJSON(['ok' => true, 'touched' => $touched, 'action' => $action]);
    }

    /** Export selected rows as CSV — works for any table where columns are safe-to-print. */
    public function export($table)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $this->request->getGet('ids'))));
        if (empty($ids)) return redirect()->back();

        $allowed = ['products','orders','intents','blogs'];
        if (! in_array($table, $allowed, true)) return redirect()->back();

        $cols = match ($table) {
            'products' => ['id','sku','slug','name','type','status','published_at'],
            'orders'   => ['id','order_number','name','email','phone','status','grand_total','created_at'],
            'intents'  => ['id','kind','name','phone','email','status','created_at'],
            'blogs'    => ['id','slug','title','status','views_count','published_at'],
        };
        $rows = Database::connect()->table($table)->select(implode(',', $cols))->whereIn('id', $ids)->get()->getResultArray();

        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, $cols);
        foreach ($rows as $r) fputcsv($fh, $r);
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $table . '-export-' . date('Ymd-His') . '.csv"')
            ->setBody($csv);
    }

    private function fail(string $msg)
    {
        return $this->response->setJSON(['ok' => false, 'error' => $msg]);
    }
}
