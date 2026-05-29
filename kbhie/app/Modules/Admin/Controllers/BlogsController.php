<?php

namespace App\Modules\Admin\Controllers;

use Config\Database;

class BlogsController extends BaseAdminController
{
    public function index()
    {
        $rows = Database::connect()->table('blogs')->orderBy('created_at', 'DESC')->limit(100)->get()->getResultArray();
        return $this->view('App\Modules\Admin\Views\blogs_index', [
            'page' => ['title' => 'Blog posts — Khoobie Admin'],
            'rows' => $rows,
        ]);
    }

    public function new()
    {
        return $this->view('App\Modules\Admin\Views\blogs_edit', [
            'page' => ['title' => 'New blog post — Khoobie Admin'],
            'row'  => null,
        ]);
    }

    public function create()
    {
        $id = $this->savePayload(null);
        return redirect()->to('/admin/blogs/' . $id . '/edit');
    }

    public function edit($id = null)
    {
        $row = Database::connect()->table('blogs')->where('id', (int) $id)->get()->getRowArray();
        if (! $row) return redirect()->to('/admin/blogs');
        return $this->view('App\Modules\Admin\Views\blogs_edit', [
            'page' => ['title' => esc($row['title']) . ' — Khoobie Admin'],
            'row'  => $row,
        ]);
    }

    public function update($id = null)
    {
        $this->savePayload((int) $id);
        return redirect()->to('/admin/blogs/' . $id . '/edit')->with('success', 'Saved.');
    }

    public function delete($id = null)
    {
        Database::connect()->table('blogs')->where('id', (int) $id)->delete();
        return redirect()->to('/admin/blogs');
    }

    private function savePayload(?int $id): int
    {
        $db = Database::connect();
        $title = trim((string) $this->request->getPost('title'));
        $slug  = url_title(strtolower($this->request->getPost('slug') ?: $title), '-', true);

        $data = [
            'title'           => $title,
            'slug'            => $slug,
            'excerpt'         => $this->request->getPost('excerpt'),
            'body_md'         => $this->request->getPost('body_md'),
            'hero_image'      => $this->request->getPost('hero_image'),
            'tags'            => $this->request->getPost('tags'),
            'author_name'     => $this->request->getPost('author_name') ?: 'Khoobie Editorial',
            'ai_generated'    => (int) $this->request->getPost('ai_generated'),
            'seo_title'       => $this->request->getPost('seo_title'),
            'seo_description' => $this->request->getPost('seo_description'),
            'status'          => $this->request->getPost('status') ?: 'draft',
            'published_at'    => $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null,
        ];

        if ($id) { $db->table('blogs')->where('id', $id)->update($data); return $id; }
        $db->table('blogs')->insert($data);
        return (int) $db->insertID();
    }
}
