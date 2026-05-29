<?php

namespace App\Commands;

use App\Libraries\LLM\LLMService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * php spark ai:descriptions [--limit=20] [--dry-run]
 *
 * Batch-fills missing short_desc / long_desc for products using the LLM.
 * Run after bulk imports to make sure every product has SEO-grade copy.
 */
class AiDescriptions extends BaseCommand
{
    protected $group       = 'Khoobie';
    protected $name        = 'ai:descriptions';
    protected $description = 'Fills empty/short product descriptions with AI-generated copy.';
    protected $usage       = 'ai:descriptions [--limit=20] [--dry-run]';

    public function run(array $params)
    {
        $limit  = (int) (CLI::getOption('limit') ?: 20);
        $dryRun = (bool) CLI::getOption('dry-run');

        $db = Database::connect();
        $products = $db->table('products')
            ->select('id, name, short_desc, long_desc, type, age_min_years, age_max_years')
            ->where('status', 'active')
            ->groupStart()
                ->where('short_desc IS NULL', null, false)
                ->orWhere('CHAR_LENGTH(short_desc) <', 40, false)
                ->orWhere('long_desc IS NULL', null, false)
                ->orWhere('CHAR_LENGTH(long_desc) <', 100, false)
            ->groupEnd()
            ->limit($limit)->get()->getResultArray();

        if (empty($products)) { CLI::write('Nothing to fill — all products have good copy.', 'green'); return; }

        $llm = new LLMService();
        $done = 0;

        foreach ($products as $p) {
            CLI::write("→ #{$p['id']} {$p['name']}", 'cyan');
            $updates = [];

            if (empty($p['short_desc']) || strlen($p['short_desc']) < 40) {
                $text = $llm->generateProductDescription($p, 80);
                if ($text) { $updates['short_desc'] = $text; CLI::write("  short: " . substr($text, 0, 80) . '…'); }
            }
            if (empty($p['long_desc']) || strlen($p['long_desc']) < 100) {
                $text = $llm->generateProductDescription($p, 200);
                if ($text) { $updates['long_desc']  = $text; CLI::write("  long:  " . substr($text, 0, 80) . '…'); }
            }
            if (! $updates) continue;

            if (! $dryRun) {
                $db->table('products')->where('id', $p['id'])->update($updates);
                $done++;
            }
        }
        CLI::write("Updated {$done} product(s).", 'green');
    }
}
