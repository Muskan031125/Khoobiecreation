<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

/**
 * Unified Lead Inbox — surfaces both legacy `leads` table rows AND the new
 * `intents` table (trial / RSVP / reserve-seat / discovery-call etc.) in one
 * actionable place for the ops team.
 */
class LeadsController extends BaseAdminController
{
    public function index()
    {
        $db = Database::connect();

        $kind   = $this->request->getGet('kind')   ?: '';
        $status = $this->request->getGet('status') ?: '';
        $q      = trim((string) $this->request->getGet('q'));

        // Build intent query
        $b = $db->table('intents i')
            ->select('i.*, p.name AS product_name, p.slug AS product_slug, p.type AS product_type')
            ->join('products p', 'p.id = i.product_id', 'left');
        if ($kind)   $b->where('i.kind',   $kind);
        if ($status) $b->where('i.status', $status);
        if ($q) {
            $b->groupStart()
                ->like('i.name', $q)
                ->orLike('i.phone', $q)
                ->orLike('i.email', $q)
                ->orLike('p.name', $q)
            ->groupEnd();
        }
        $intents = $b->orderBy('i.id', 'DESC')->limit(100)->get()->getResultArray();

        // Aggregate counts for filter chips
        $counts = [];
        foreach ($db->table('intents')->select('kind, COUNT(*) AS n')->groupBy('kind')->get()->getResultArray() as $r) {
            $counts['kind:' . $r['kind']] = (int) $r['n'];
        }
        foreach ($db->table('intents')->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $r) {
            $counts['status:' . $r['status']] = (int) $r['n'];
        }
        $counts['total'] = (int) $db->table('intents')->countAllResults();

        return $this->view('App\Modules\Admin\Views\leads_inbox', [
            'page'    => ['title' => 'Lead Inbox — Khoobie Admin'],
            'intents' => $intents,
            'counts'  => $counts,
            'filters' => ['kind' => $kind, 'status' => $status, 'q' => $q],
        ]);
    }

    public function show($id = null)
    {
        $db = Database::connect();
        $row = $db->table('intents i')
            ->select('i.*, p.name AS product_name, p.slug AS product_slug, p.type AS product_type')
            ->join('products p', 'p.id = i.product_id', 'left')
            ->where('i.id', (int) $id)->get()->getRowArray();
        if (! $row) return redirect()->to('/admin/leads');

        return $this->view('App\Modules\Admin\Views\leads_show', [
            'page' => ['title' => 'Lead #' . $row['id'] . ' — Khoobie Admin'],
            'row'  => $row,
        ]);
    }

    /** Mark contacted / converted / cancelled */
    public function setStatus($id = null)
    {
        $next = (string) $this->request->getPost('status');
        $allowed = ['pending','verified','reserved','converted','contacted','cancelled','no_show'];
        if (! in_array($next, $allowed, true)) return redirect()->back();
        Database::connect()->table('intents')->where('id', (int) $id)->update([
            'status'     => $next,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->back()->with('success', 'Lead updated.');
    }
}
