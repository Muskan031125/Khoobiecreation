<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

class ReviewsController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();
        $status = $this->request->getGet('status') ?: 'pending';

        $rows = $db->table('reviews r')
            ->select('r.*, p.name AS product_name, p.slug AS product_slug')
            ->join('products p', 'p.id = r.product_id')
            ->where('r.status', $status)
            ->orderBy('r.created_at', 'DESC')
            ->limit(200)->get()->getResultArray();

        $counts = [];
        foreach ($db->table('reviews')->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }

        return $this->view('App\Modules\Admin\Views\reviews_index', [
            'page'   => ['title' => 'Reviews Moderation'],
            'rows'   => $rows,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function approve($id)
    {
        $this->setStatus((int) $id, 'published');
        $this->recomputeRating($id);
        return redirect()->to('/admin/reviews')->with('success', 'Approved.');
    }

    public function reject($id)
    {
        $this->setStatus((int) $id, 'rejected');
        return redirect()->to('/admin/reviews')->with('success', 'Rejected.');
    }

    private function setStatus(int $id, string $status): void
    {
        Database::connect()->table('reviews')->where('id', $id)->update([
            'status'      => $status,
            'moderated_at'=> date('Y-m-d H:i:s'),
            'moderated_by'=> session('user')['id'] ?? null,
        ]);
    }

    /** Recompute product rating_avg + rating_count from PUBLISHED reviews only. */
    private function recomputeRating(int $reviewId): void
    {
        $db  = Database::connect();
        $row = $db->table('reviews')->where('id', $reviewId)->get()->getRow();
        if (! $row) return;
        $agg = $db->query("SELECT COUNT(*) AS n, COALESCE(AVG(rating),0) AS a FROM reviews WHERE product_id = ? AND status = 'published'", [$row->product_id])->getRow();
        $db->table('products')->where('id', $row->product_id)->update([
            'rating_count' => (int) $agg->n,
            'rating_avg'   => round((float) $agg->a, 2),
        ]);
    }
}
