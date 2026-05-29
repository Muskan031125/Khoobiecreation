<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\MarketplaceImporter;
use Config\Database;

/**
 * Admin URL Importer.
 *   GET  /admin/import           — paste URL + recent imports list
 *   POST /admin/import/fetch     — AJAX: fetch + LLM extract → return draft JSON
 *   POST /admin/import/save      — persist edited draft as a real product
 */
class ImportController extends BaseAdminController
{
    public function index()
    {
        // Recent imports — products created via importer
        $recent = Database::connect()->table('products')
            ->select('id, sku, name, slug, status, hero_image, created_at')
            ->like('sku', 'KK-IMP-', 'after')
            ->orderBy('created_at', 'DESC')
            ->limit(20)->get()->getResultArray();

        $categories = Database::connect()->table('categories')
            ->where('is_active', 1)
            ->orderBy('parent_id', 'ASC')->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        return $this->view('App\Modules\Admin\Views\import', [
            'page'       => ['title' => 'Import from URL — Khoobie Admin'],
            'recent'     => $recent,
            'categories' => $categories,
        ]);
    }

    public function fetch()
    {
        $url = trim((string) $this->request->getPost('url'));
        if (! $url) return $this->response->setJSON(['ok' => false, 'error' => 'URL required']);

        $importer = new MarketplaceImporter();
        $res = $importer->importFromUrl($url);
        return $this->response->setJSON($res);
    }

    public function save()
    {
        $draft = json_decode((string) $this->request->getPost('draft'), true);
        if (! is_array($draft) || empty($draft['name'])) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Invalid draft']);
        }
        $categoryId = (int) $this->request->getPost('category_id') ?: null;

        $importer = new MarketplaceImporter();
        $res = $importer->persistDraft($draft, $categoryId);
        return $this->response->setJSON($res);
    }
}
