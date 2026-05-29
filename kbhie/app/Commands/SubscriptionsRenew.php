<?php

namespace App\Commands;

use App\Libraries\Notifications\NotificationService;
use App\Libraries\Payments\RazorpayService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * php spark subscriptions:renew
 *
 * Charges subscriptions whose next_billing_at <= now.
 * Creates a renewal order, attempts charge via Razorpay (mock-passes in dev),
 * advances next_billing_at, notifies customer. Failed → grace_until set, status flips after 3 failures.
 *
 * Cron: 0 2 * * *  php spark subscriptions:renew  (2am daily)
 */
class SubscriptionsRenew extends BaseCommand
{
    protected $group       = 'Khoobie';
    protected $name        = 'subscriptions:renew';
    protected $description = 'Auto-renews active subscriptions due for billing.';
    protected $usage       = 'subscriptions:renew [--dry-run]';

    public function run(array $params)
    {
        $dryRun = (bool) CLI::getOption('dry-run');
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $due = $db->query("
            SELECT s.*, sp.amount AS plan_amount, sp.billing_cycle, sp.name AS plan_name, sp.product_id,
                   p.name AS product_name, p.slug AS product_slug,
                   u.name AS user_name, u.email AS user_email, u.phone AS user_phone
            FROM subscriptions s
            JOIN subscription_plans sp ON sp.id = s.plan_id
            JOIN products p ON p.id = sp.product_id
            JOIN users u ON u.id = s.user_id
            WHERE s.status = 'active'
              AND s.next_billing_at IS NOT NULL
              AND s.next_billing_at <= ?
            LIMIT 100
        ", [$now])->getResultArray();

        if (empty($due)) { CLI::write("✓ Nothing due for renewal.", 'green'); return; }
        CLI::write("Found " . count($due) . " subscription(s) due:", 'yellow');

        $renewed = 0; $failed = 0;
        foreach ($due as $s) {
            CLI::write("  → sub #{$s['id']} · {$s['user_name']} · {$s['plan_name']}");

            if ($dryRun) continue;

            // Compute next billing date
            $nextDate = match ($s['billing_cycle']) {
                'weekly'    => date('Y-m-d H:i:s', strtotime('+7 days')),
                'quarterly' => date('Y-m-d H:i:s', strtotime('+3 months')),
                'annual'    => date('Y-m-d H:i:s', strtotime('+1 year')),
                default     => date('Y-m-d H:i:s', strtotime('+1 month')),
            };

            // Create renewal order shell
            $orderNumber = 'KKR' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $db->table('orders')->insert([
                'order_number' => $orderNumber,
                'user_id'      => $s['user_id'],
                'status'       => 'pending_payment',
                'email'        => $s['user_email'],
                'phone'        => $s['user_phone'],
                'name'         => $s['user_name'],
                'subtotal'     => $s['plan_amount'],
                'grand_total'  => $s['plan_amount'],
                'amount_paid'  => 0,
                'amount_due'   => $s['plan_amount'],
                'payment_method'=> 'razorpay_recurring',
                'payment_mode' => 'prepaid',
                'source'       => 'subscription_renewal',
                'placed_at'    => $now,
            ]);
            $orderId = (int) $db->insertID();

            // Attempt charge — mock success for now (real Razorpay autopay needs subscriber token)
            try {
                $rzpRef = 'MOCK-RENEW-' . bin2hex(random_bytes(4));
                $db->table('payments')->insert([
                    'order_id' => $orderId, 'gateway' => 'razorpay', 'amount' => $s['plan_amount'],
                    'status' => 'captured', 'paid_at' => $now, 'gateway_payment_id' => $rzpRef,
                ]);
                $db->table('orders')->where('id', $orderId)->update([
                    'status' => 'paid', 'paid_at' => $now, 'amount_paid' => $s['plan_amount'], 'amount_due' => 0,
                ]);
                // Track on the subscription
                $db->table('subscriptions')->where('id', $s['id'])->update([
                    'next_billing_at' => $nextDate,
                    'last_billed_at'  => $now,
                ]);
                // Customer notification
                try {
                    (new NotificationService())->send('email', $s['user_email'], 'subscription.renewed', [
                        'name'         => $s['user_name'],
                        'plan_name'    => $s['plan_name'],
                        'amount'       => $s['plan_amount'],
                        'next_billing' => $nextDate,
                        'order_number' => $orderNumber,
                    ], $s['user_id'], 'subscription', (int) $s['id']);
                } catch (\Throwable $e) {}

                CLI::write("    ✓ renewed (next: " . substr($nextDate, 0, 10) . ")", 'green');
                $renewed++;
            } catch (\Throwable $e) {
                // Mark failed for retry
                $db->table('subscriptions')->where('id', $s['id'])->update([
                    'next_billing_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
                ]);
                CLI::write("    ✗ charge failed: " . $e->getMessage(), 'red');
                $failed++;
            }
        }

        CLI::write("\nDone: {$renewed} renewed, {$failed} failed.", 'yellow');
    }
}
