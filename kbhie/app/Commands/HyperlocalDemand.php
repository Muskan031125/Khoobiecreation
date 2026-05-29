<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * php spark hyperlocal:demand
 *
 * Finds (city, locality) buckets where ≥ THRESHOLD users have shown intent
 * (search, intent capture, recently-viewed) for offline products in the last
 * N days but where Khoobie has fewer than MIN supply listings.
 *
 * Output: a daily "supply gap" report — perfect for the ops team to
 * proactively recruit instructors in under-served localities.
 *
 * Cron: 0 9 * * 1   (every Monday at 9am)
 */
class HyperlocalDemand extends BaseCommand
{
    protected $group       = 'Khoobie';
    protected $name        = 'hyperlocal:demand';
    protected $description = 'Identifies city/locality combos with demand > supply for offline products.';
    protected $usage       = 'hyperlocal:demand [--days=30] [--threshold=5] [--min-supply=3]';

    public function run(array $params)
    {
        $days      = (int) (CLI::getOption('days') ?: 30);
        $threshold = (int) (CLI::getOption('threshold') ?: 5);
        $minSupply = (int) (CLI::getOption('min-supply') ?: 3);

        $db = Database::connect();
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Demand: orders + intents for offline product types per (city, locality) over the window
        $demand = $db->query("
            SELECT m.city, m.locality, COUNT(DISTINCT COALESCE(o.user_id, o.email, o.phone)) AS interested_users
            FROM intents i
            JOIN products p ON p.id = i.product_id
            JOIN meetups m  ON m.product_id = p.id
            LEFT JOIN orders o ON o.id = i.id  -- harmless join for grouping
            WHERE i.created_at >= ?
              AND p.type IN ('meetup','service','tuition')
              AND m.city IS NOT NULL
            GROUP BY m.city, m.locality
        ", [$cutoff])->getResultArray();

        // Supply: count of active products per (city, locality) — what we already offer
        $supply = [];
        foreach ($db->query("
            SELECT city, locality, COUNT(*) AS supply_n FROM meetups
            WHERE city IS NOT NULL
            GROUP BY city, locality
        ")->getResultArray() as $r) {
            $key = $r['city'] . '|' . ($r['locality'] ?? '');
            $supply[$key] = (int) $r['supply_n'];
        }

        $gaps = [];
        foreach ($demand as $d) {
            $key = $d['city'] . '|' . ($d['locality'] ?? '');
            $supplyN = $supply[$key] ?? 0;
            if ((int) $d['interested_users'] >= $threshold && $supplyN < $minSupply) {
                $gaps[] = [
                    'city'             => $d['city'],
                    'locality'         => $d['locality'] ?: '—',
                    'interested_users' => (int) $d['interested_users'],
                    'current_supply'   => $supplyN,
                ];
            }
        }

        if (empty($gaps)) {
            CLI::write("✓ No supply gaps detected (looking at {$days}-day window, threshold={$threshold} users).", 'green');
            return;
        }

        // Sort by demand desc
        usort($gaps, fn ($a, $b) => $b['interested_users'] - $a['interested_users']);

        CLI::write("⚠ Found " . count($gaps) . " (city, locality) combos where demand exceeds supply:", 'yellow');
        CLI::table(['City', 'Locality', 'Interested', 'Supply'], array_map(fn ($g) => [$g['city'], $g['locality'], $g['interested_users'], $g['current_supply']], $gaps));

        // Email ops the report
        try {
            $rows  = '';
            foreach ($gaps as $g) {
                $rows .= "<tr><td>{$g['city']}</td><td>{$g['locality']}</td><td><strong>{$g['interested_users']}</strong></td><td>{$g['current_supply']}</td></tr>";
            }
            $html = "<h2>Hyperlocal supply gaps — last {$days} days</h2>"
                  . "<p>Recruit instructors / studios in these areas:</p>"
                  . "<table border='1' cellpadding='8' cellspacing='0'><thead><tr><th>City</th><th>Locality</th><th>Interested users</th><th>Current supply</th></tr></thead><tbody>{$rows}</tbody></table>";
            (new \App\Libraries\Notifications\NotificationService())->send(
                'email', env('khoobie.support_email', 'ops@khoobie.com'),
                'admin.hyperlocal_demand',
                ['message' => $html, 'count' => count($gaps)]
            );
            CLI::write('  → emailed to ops', 'cyan');
        } catch (\Throwable $e) {}

        // Persist to a simple log table for the admin map UI later
        try {
            $db->table('hyperlocal_demand_snapshots')->insert([
                'snapshot_date' => date('Y-m-d'),
                'window_days'   => $days,
                'threshold'     => $threshold,
                'gaps_json'     => json_encode($gaps),
            ]);
        } catch (\Throwable $e) { /* table optional */ }
    }
}
