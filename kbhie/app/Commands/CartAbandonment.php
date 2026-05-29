<?php

namespace App\Commands;

use App\Libraries\LLM\LLMService;
use App\Libraries\Notifications\NotificationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * php spark cart:abandonment
 *
 * Finds carts updated > 1h ago with items but no order, sends an
 * LLM-personalised WhatsApp + email recovery nudge. Tracks "sent" so we
 * don't spam the same cart again within 7 days.
 *
 * Run nightly via cron:  0 19 * * *  php spark cart:abandonment
 */
class CartAbandonment extends BaseCommand
{
    protected $group       = 'Khoobie';
    protected $name        = 'cart:abandonment';
    protected $description = 'Sends WhatsApp + email recovery nudges to abandoned carts.';
    protected $usage       = 'cart:abandonment [--dry-run] [--max=10]';

    public function run(array $params)
    {
        $dryRun = (bool) CLI::getOption('dry-run');
        $max    = (int) (CLI::getOption('max') ?: 25);

        $db = Database::connect();
        $cutoff = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $within = date('Y-m-d H:i:s', strtotime('-7 days'));

        // Carts updated 1h–7d ago, with items, no order placed, no recent nudge sent
        $carts = $db->query("
            SELECT c.id, c.user_id, c.anon_id, c.item_count, c.grand_total, c.updated_at,
                   u.name, u.email, u.phone
            FROM carts c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.item_count > 0
              AND c.updated_at <= ?
              AND c.updated_at >= ?
              AND NOT EXISTS (SELECT 1 FROM orders o WHERE o.user_id = c.user_id AND o.created_at >= c.updated_at)
              AND NOT EXISTS (
                  SELECT 1 FROM notifications_log nl
                  WHERE nl.ref_type = 'cart' AND nl.ref_id = c.id
                  AND nl.template_key = 'cart.abandoned' AND nl.queued_at >= ?
              )
              AND (u.email IS NOT NULL OR u.phone IS NOT NULL)
            ORDER BY c.grand_total DESC
            LIMIT ?
        ", [$cutoff, $within, $within, $max])->getResultArray();

        CLI::write(sprintf('Found %d abandoned carts to nudge.', count($carts)), 'yellow');
        if (empty($carts)) return;

        $llm   = new LLMService();
        $notif = new NotificationService();

        foreach ($carts as $c) {
            $items = $db->query("SELECT product_name FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.cart_id = ?", [$c['id']])->getResultArray();
            $itemList = implode(', ', array_map(fn ($i) => $i['product_name'], $items));

            // Personalise via LLM (graceful fallback if no key)
            $msg = "Hi " . ($c['name'] ?: 'there') . "! You left these in your Khoobie cart: {$itemList}. Total ₹" . number_format(round($c['grand_total']/100)) . ". Use code WELCOME10 for 10% off if it helps. — Team Khoobie";
            $llmRes = $llm->complete(
                "Write a friendly, conversational 50-word WhatsApp message reminding a parent they left items in their cart: {$itemList} totalling ₹" . round($c['grand_total']/100) . ". Mention WELCOME10 code for 10% off. No emojis. Indian English. Signed: Team Khoobie.",
                ['max_tokens' => 200, 'temperature' => 0.7]
            );
            if (! empty($llmRes['text'])) $msg = trim($llmRes['text']);

            CLI::write(sprintf('  → cart #%d (%s): %s', $c['id'], $c['phone'] ?: $c['email'], substr($msg, 0, 60) . '…'));

            if (! $dryRun) {
                $payload = ['message' => $msg, 'cart_url' => rtrim(base_url(), '/') . '/cart'];
                if ($c['phone']) $notif->send('whatsapp', $c['phone'], 'cart.abandoned', $payload, $c['user_id'], 'cart', (int) $c['id']);
                if ($c['email']) $notif->send('email',    $c['email'], 'cart.abandoned', $payload, $c['user_id'], 'cart', (int) $c['id']);
            }
        }

        CLI::write('Done.', 'green');
    }
}
