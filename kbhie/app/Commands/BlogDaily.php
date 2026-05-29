<?php

namespace App\Commands;

use App\Libraries\LLM\LLMService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * php spark blog:daily
 *
 * Picks a trending topic, drafts a 800-word post, drops into blogs table
 * as `draft` for human review before publishing.
 *
 * Cron: 0 8 * * *  php spark blog:daily
 */
class BlogDaily extends BaseCommand
{
    protected $group       = 'Khoobie';
    protected $name        = 'blog:daily';
    protected $description = 'Drafts a daily blog post (status=draft) for human review.';
    protected $usage       = 'blog:daily [--topic=<text>] [--keywords=<comma>]';

    // Editorial topic pool — rotated daily. Each is an India-rooted angle.
    private array $topicPool = [
        ['Best return gifts for kids\' birthday parties in Mumbai under ₹500', 'return gifts, kids birthday, mumbai, bulk gifts'],
        ['How to keep your 7-year-old off screens for the summer holidays', 'screen-free, summer holidays, kids activities, indian parents'],
        ['Vedic Maths vs Abacus: which is better for your child?', 'vedic maths, abacus, mental math, indian education'],
        ['10 weekend craft kits to do with your child during monsoon', 'craft kits, monsoon activities, indian rainy day, kids'],
        ['Bharatanatyam vs Kathak: helping your child pick a classical dance', 'bharatanatyam, kathak, classical dance for kids, indian culture'],
        ['The new Indian parent\'s guide to chess for under-10s', 'chess for kids, fide rating, gukesh, kids chess india'],
        ['Why pottery is the perfect anti-tablet activity for young kids', 'pottery for kids, clay modelling, screen-free, indian craft'],
        ['Sanskrit sloka recitation for kids: where to start', 'sanskrit, slokas, devotional, indian heritage, kids'],
        ['5 hyperlocal Bangalore activities for kids this weekend', 'bangalore kids, weekend activities, indiranagar, koramangala'],
        ['Madhubani painting class: a complete beginner\'s guide for parents', 'madhubani, folk art, kids art class, bihar craft'],
    ];

    public function run(array $params)
    {
        $topic    = CLI::getOption('topic')    ?: null;
        $keywords = CLI::getOption('keywords') ?: null;

        if (! $topic) {
            // Rotate based on day-of-year so we don't repeat within ~10 days
            $idx = ((int) date('z')) % count($this->topicPool);
            [$topic, $keywords] = $this->topicPool[$idx];
        }

        $db = Database::connect();
        $existing = $db->table('blogs')->like('title', $topic)->countAllResults();
        if ($existing) { CLI::write("Skipping — a post with topic '{$topic}' already exists.", 'yellow'); return; }

        CLI::write("→ Drafting: {$topic}", 'cyan');
        $llm = new LLMService();

        $system = 'You are a Khoobie editorial blog writer. Tone: warm, India-rooted, parent-trustworthy. Indian English. No emojis. Markdown headings.';
        $body = $llm->complete(
            "Write a 800-word blog post.\nTopic: {$topic}\nKeywords: {$keywords}\n\nReturn Markdown only. Start with H2 sections, not a title.",
            ['max_tokens' => 3500, 'temperature' => 0.7, 'system' => $system]
        );
        $meta = $llm->complete(
            "Write a blog title (≤60 chars) and meta description (≤160 chars) for: {$topic}. Return JSON: {\"title\":\"...\",\"meta\":\"...\"}",
            ['max_tokens' => 200, 'temperature' => 0.5]
        );

        $title = $topic; $metaDesc = '';
        if (preg_match('/\{.*\}/s', (string) $meta['text'], $m)) {
            $j = json_decode($m[0], true) ?: [];
            $title    = $j['title'] ?? $topic;
            $metaDesc = $j['meta']  ?? '';
        }

        $slug = url_title(strtolower($title), '-', true);
        $db->table('blogs')->insert([
            'title'           => $title,
            'slug'            => $slug,
            'body_md'         => (string) $body['text'],
            'excerpt'         => substr(strip_tags($body['text'] ?? ''), 0, 180),
            'ai_generated'    => 1,
            'seo_title'       => $title,
            'seo_description' => $metaDesc,
            'status'          => 'draft',
            'author_name'     => 'Khoobie Editorial (AI-drafted)',
        ]);
        $id = (int) $db->insertID();
        CLI::write("  ✓ Draft #{$id} saved. Review at /admin/blogs/{$id}/edit", 'green');
    }
}
