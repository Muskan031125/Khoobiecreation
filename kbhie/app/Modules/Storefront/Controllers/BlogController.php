<?php

namespace App\Modules\Storefront\Controllers;

use Config\Database;

class BlogController extends BaseStoreController
{
    public function index()
    {
        $rows = Database::connect()->table('blogs')
            ->where('status', 'published')
            ->orderBy('published_at', 'DESC')
            ->limit(50)->get()->getResultArray();

        return $this->view('App\Modules\Storefront\Views\blog_index', [
            'page' => array_merge($this->data['page'], [
                'title'       => 'The Khoobie Blog — screen-free parenting ideas',
                'description' => 'Practical, India-rooted ideas for screen-free kids — by parents, for parents.',
            ]),
            'rows' => $rows,
        ]);
    }

    public function show(string $slug)
    {
        $db = Database::connect();
        $row = $db->table('blogs')->where('slug', $slug)->where('status', 'published')->get()->getRowArray();
        if (! $row) return redirect()->to('/blog');

        $db->table('blogs')->where('id', $row['id'])->set('views_count', 'views_count + 1', false)->update();

        $related = $db->table('blogs')
            ->where('status', 'published')->where('id !=', $row['id'])
            ->orderBy('published_at', 'DESC')->limit(3)->get()->getResultArray();

        return $this->view('App\Modules\Storefront\Views\blog_show', [
            'page' => array_merge($this->data['page'], [
                'title'       => $row['seo_title']       ?: ($row['title'] . ' — Khoobie Blog'),
                'description' => $row['seo_description'] ?: $row['excerpt'],
                'type'        => 'article',
                'image'       => $row['hero_image'] ?: base_url('assets/og-default.jpg'),
            ]),
            'row'     => $row,
            'related' => $related,
            'html'    => $this->mdToHtml($row['body_md']),
        ]);
    }

    /** Minimal MD→HTML — good enough for AI-generated posts using headings, lists, paragraphs, links, bold. */
    private function mdToHtml(string $md): string
    {
        // Headings
        $md = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $md);
        $md = preg_replace('/^## (.+)$/m',  '<h2>$1</h2>', $md);
        $md = preg_replace('/^# (.+)$/m',   '<h1>$1</h1>', $md);
        // Bold + italic
        $md = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $md);
        $md = preg_replace('/\*(.+?)\*/',     '<em>$1</em>', $md);
        // Links [text](url)
        $md = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="text-brand-600 hover:underline">$1</a>', $md);
        // Lists — group consecutive lines starting with - into <ul>
        $md = preg_replace_callback('/((?:^- .+\n?)+)/m', function ($m) {
            $lis = preg_replace('/^- (.+)$/m', '<li>$1</li>', trim($m[1]));
            return "<ul>{$lis}</ul>";
        }, $md);
        // Paragraphs — split on blank lines, wrap each non-block in <p>
        $blocks = preg_split('/\n{2,}/', trim($md));
        $out = '';
        foreach ($blocks as $b) {
            $b = trim($b);
            if (! $b) continue;
            if (preg_match('/^<(h[1-6]|ul|ol|blockquote|pre|p)/', $b)) $out .= $b . "\n";
            else $out .= '<p>' . $b . "</p>\n";
        }
        return $out;
    }
}
