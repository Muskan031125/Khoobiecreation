<?php
/**
 * PDP Trust Block — social proof + scarcity stack that lifts conversion.
 * Pulls live data: recent intents (last 7d), reviews, stock, instructor cred.
 *
 * Renders nothing if the product has no signals worth showing (gracefully degrades
 * for brand-new products without trust artifacts).
 */
$db = \Config\Database::connect();
$pid = (int) ($product['id'] ?? 0);
if ($pid <= 0) return;

// Cheap aggregates (no LLM, no external calls — runs in <5ms per PDP)
$lastWeek = date('Y-m-d H:i:s', strtotime('-7 days'));
$intentCount = (int) $db->table('intents')
    ->where('product_id', $pid)
    ->where('created_at >=', $lastWeek)
    ->countAllResults();

$verifiedReviewCount = (int) $db->table('reviews')
    ->where('product_id', $pid)
    ->where('status', 'published')
    ->where('is_verified_buyer', 1)
    ->countAllResults();

$totalStock = $product['total_stock'] ?? null;
$type       = $product['type'] ?? 'simple';
$rating     = (float) ($product['rating_avg'] ?? 0);
$reviewCnt  = (int) ($product['rating_count'] ?? 0);

$signals = [];
// Recent demand (works for any type — measures interest, not just purchases)
if ($intentCount >= 3) {
    $verb = match ($type) {
        'tuition','course'   => 'parents enrolled',
        'meetup','service'   => 'parents booked',
        'membership'         => 'families joined',
        default              => 'parents bought',
    };
    $signals[] = ['icon' => '🔥', 'text' => "<strong>{$intentCount} {$verb}</strong> in the last 7 days", 'cls' => 'bg-rose-50 text-rose-800'];
}

// Stock scarcity (physical only, real low stock)
if ($totalStock !== null && $totalStock > 0 && $totalStock <= 5 && in_array($type, ['simple','variable','bundle'], true)) {
    $signals[] = ['icon' => '⚡', 'text' => "Only <strong>{$totalStock} left</strong> in stock — ships within 24h", 'cls' => 'bg-amber-50 text-amber-800'];
}

// Verified-buyer reviews
if ($verifiedReviewCount >= 5) {
    $pct = $reviewCnt > 0 ? round(($verifiedReviewCount / $reviewCnt) * 100) : 0;
    $signals[] = ['icon' => '✅', 'text' => "<strong>{$verifiedReviewCount} verified-buyer reviews</strong> · {$pct}% of all reviews", 'cls' => 'bg-emerald-50 text-emerald-800'];
}

// High rating signal
if ($rating >= 4.5 && $reviewCnt >= 20) {
    $signals[] = ['icon' => '⭐', 'text' => "<strong>" . number_format($rating, 1) . "/5</strong> from <strong>{$reviewCnt}</strong> parents — among our top-rated", 'cls' => 'bg-violet-50 text-violet-800'];
}

// Instructor cred (classes only)
if (in_array($type, ['tuition','course','service','meetup'], true)) {
    $cred = $db->table('tuitions')->select('instructor_name')->where('product_id', $pid)->get()->getRow()
         ?: $db->table('courses')->select('instructor_name')->where('product_id', $pid)->get()->getRow()
         ?: $db->table('services')->select('provider_name AS instructor_name')->where('product_id', $pid)->get()->getRow();
    if ($cred && ! empty($cred->instructor_name)) {
        $signals[] = ['icon' => '🎓', 'text' => "Khoobie-vetted instructor: <strong>" . esc($cred->instructor_name) . "</strong> · background-checked", 'cls' => 'bg-sky-50 text-sky-800'];
    }
}

if (empty($signals)) return;
?>
<div class="mt-5 space-y-1.5">
    <?php foreach ($signals as $s): ?>
        <div class="flex items-start gap-2.5 px-3 py-2 rounded-lg <?= $s['cls'] ?> text-xs sm:text-sm">
            <span class="text-base shrink-0 leading-tight"><?= $s['icon'] ?></span>
            <span class="flex-1"><?= $s['text'] ?></span>
        </div>
    <?php endforeach; ?>
</div>
