<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

/**
 * Generic admin list-only controller used by most resource pages
 * (leads, customers, coupons, promotions, etc.). Subclasses set
 * $table, $title, $listColumns. Index supports pagination + sortable
 * columns + filter chips.
 */
abstract class GenericController extends BaseAdminController
{
    protected string $table = '';
    protected string $title = '';
    protected array $listColumns = [];           // ['id', 'name', ...]
    protected array $sortableColumns = [];       // subset of $listColumns that should be header-clickable
    protected array $searchColumns = [];         // ['name','email']
    protected string $defaultSort = 'id';
    protected string $defaultSortDir = 'DESC';
    protected array $perPageOptions = [25, 50, 100, 250];

    public function index()
    {
        $db = Database::connect();
        $req = $this->request;

        $page    = max(1, (int) $req->getGet('page'));
        $perPage = (int) ($req->getGet('per_page') ?? $this->perPageOptions[0]);
        if (! in_array($perPage, $this->perPageOptions, true)) $perPage = $this->perPageOptions[0];

        $sort    = $req->getGet('sort') ?: $this->defaultSort;
        $sortDir = strtoupper($req->getGet('dir') ?: $this->defaultSortDir);
        if (! in_array($sortDir, ['ASC', 'DESC'], true)) $sortDir = 'DESC';
        $sortable = $this->sortableColumns ?: $this->listColumns;
        if (! in_array($sort, $sortable, true)) $sort = $this->defaultSort;

        $q = trim((string) $req->getGet('q'));

        $b = $db->table($this->table);
        if ($q && $this->searchColumns) {
            $b->groupStart();
            foreach ($this->searchColumns as $i => $c) {
                $i === 0 ? $b->like($c, $q) : $b->orLike($c, $q);
            }
            $b->groupEnd();
        }
        $total = (int) $b->countAllResults(false);
        $rows  = $b->orderBy($sort, $sortDir)
                   ->limit($perPage, ($page - 1) * $perPage)
                   ->get()->getResultArray();

        return view('App\Modules\Admin\Views\generic_list', array_merge($this->data, [
            'page'           => ['title' => $this->title],
            'rows'           => $rows,
            'cols'           => $this->listColumns,
            'sortableCols'   => $sortable,
            'sort'           => $sort,
            'sortDir'        => $sortDir,
            'q'              => $q,
            'title'          => $this->title,
            'table'          => $this->table,
            'currentPage'    => $page,
            'perPage'        => $perPage,
            'perPageOptions' => $this->perPageOptions,
            'totalRows'      => $total,
            'totalPages'     => max(1, (int) ceil($total / $perPage)),
        ]));
    }

    public function show($id)   { return redirect()->to('/admin/' . $this->urlSlug()); }
    public function new()       { return $this->stub(); }
    public function create()    { return redirect()->back(); }
    public function edit($id)   { return $this->stub(); }
    public function update($id) { return redirect()->back(); }
    public function delete($id) { return redirect()->back(); }

    protected function urlSlug(): string
    {
        return str_replace('_', '-', $this->table);
    }

    protected function stub()
    {
        return view('App\Modules\Admin\Views\generic_stub', array_merge($this->data, [
            'page'  => ['title' => $this->title . ' — Editing soon'],
            'title' => $this->title,
        ]));
    }
}
