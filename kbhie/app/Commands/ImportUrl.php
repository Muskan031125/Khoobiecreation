<?php

namespace App\Commands;

use App\Libraries\MarketplaceImporter;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark import:url <url> [--category=<id>] [--publish]
 *
 * One-shot or batch URL importer. Pass --batch=path/to/urls.txt to process many.
 */
class ImportUrl extends BaseCommand
{
    protected $group       = 'Khoobie';
    protected $name        = 'import:url';
    protected $description = 'Imports a product from a marketplace URL using AI.';
    protected $usage       = 'import:url <url> [--category=<id>] [--publish] [--batch=<file>]';

    public function run(array $params)
    {
        $batch = CLI::getOption('batch');
        $catId = (int) CLI::getOption('category') ?: null;

        $urls = [];
        if ($batch && is_file($batch)) {
            $urls = array_filter(array_map('trim', file($batch)));
        } elseif (! empty($params[0])) {
            $urls = [$params[0]];
        } else {
            CLI::error('Usage: php spark import:url <url>  OR  --batch=path/to/urls.txt');
            return;
        }

        $importer = new MarketplaceImporter();
        $ok = 0; $fail = 0;
        foreach ($urls as $url) {
            CLI::write("→ {$url}", 'cyan');
            $res = $importer->importFromUrl($url);
            if (! $res['ok']) { CLI::error('  ✗ ' . ($res['error'] ?? 'failed')); $fail++; continue; }
            $save = $importer->persistDraft($res['draft'], $catId);
            if ($save['ok']) {
                CLI::write("  ✓ Saved as product id={$save['product_id']} (slug={$save['slug']})", 'green');
                $ok++;
            } else {
                CLI::error('  ✗ ' . ($save['error'] ?? 'save failed'));
                $fail++;
            }
        }
        CLI::write("\nDone: {$ok} ok, {$fail} failed.", 'yellow');
    }
}
