<?php
// Type-aware Buy / Action block on the PDP.
// Expects: $product (with type + slug + variants + extras loaded by ProductController)
//
// IMPORTANT: $this->include() does NOT auto-share parent-view local variables in CI4 —
// we MUST re-derive $default here (otherwise enrol URLs become /enrol/0 → 404 silently
// and every "Book trial"/"Enrol Now"/"Reserve seat" button does nothing).
$type    = $product['type'] ?? 'simple';
$db      = \Config\Database::connect();
$default    = $product['variants'][0] ?? null;
$defaultVid = (int) ($default['id'] ?? 0);

switch ($type) {

case 'affiliate':
    // Every active marketplace row — Amazon, Flipkart, Meesho, etc.
    // Sorted by partner price ASC so the cheapest option leads.
    $affLinks = $db->table('affiliate_links')
        ->where('product_id', $product['id'])
        ->where('is_active', 1)
        ->orderBy('price_at_partner', 'ASC')
        ->get()->getResultArray();

    // Per-partner styling hints — fall back to generic for unknown partners
    $partnerStyle = static function (string $name): array {
        $key = strtolower($name);
        return match (true) {
            str_contains($key, 'amazon')   => ['bg' => 'bg-[#FF9900] hover:bg-[#E68A00]', 'icon' => '🅰️',  'tag' => 'Prime-eligible'],
            str_contains($key, 'flipkart') => ['bg' => 'bg-[#2874F0] hover:bg-[#1F5FBF]', 'icon' => '🛍️', 'tag' => 'Plus-eligible'],
            str_contains($key, 'meesho')   => ['bg' => 'bg-[#9F2089] hover:bg-[#7E1A6E]', 'icon' => '💖', 'tag' => 'Lowest price often'],
            str_contains($key, 'myntra')   => ['bg' => 'bg-[#FF3F6C] hover:bg-[#E0355E]', 'icon' => '👗', 'tag' => 'Style picks'],
            str_contains($key, 'firstcry') => ['bg' => 'bg-[#C50056] hover:bg-[#9F0044]', 'icon' => '🧸', 'tag' => 'Kid-focused'],
            str_contains($key, 'ajio')     => ['bg' => 'bg-[#FFA500] hover:bg-[#E69500]', 'icon' => '🛒', 'tag' => 'Reliance'],
            default                        => ['bg' => 'bg-slate-900 hover:bg-slate-800', 'icon' => '↗', 'tag' => ''],
        };
    };

    // Identify the cheapest row (already first after ORDER BY price ASC) to badge it.
    $cheapestId = null;
    foreach ($affLinks as $l) {
        if (! empty($l['price_at_partner'])) { $cheapestId = $l['id']; break; }
    }
    ?>
    <div class="mt-6 space-y-3">
        <?php if (count($affLinks) === 0): ?>
            <a href="<?= base_url('go/' . $product['slug']) ?>" target="_blank" rel="noopener nofollow sponsored"
               class="btn-primary w-full text-base">Buy on partner site &nbsp;↗</a>
        <?php else: ?>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                <?= count($affLinks) === 1 ? 'Buy through' : 'Compare on these marketplaces' ?>
            </div>
            <?php foreach ($affLinks as $i => $l):
                $s = $partnerStyle($l['partner_name']);
                $isCheapest = ($l['id'] === $cheapestId) && count($affLinks) > 1;
                $priceLabel = ! empty($l['price_at_partner']) ? '₹' . number_format(round((int) $l['price_at_partner'] / 100)) : null;
                $age = ! empty($l['price_updated_at']) ? (int) ((time() - strtotime($l['price_updated_at'])) / 86400) : null;
            ?>
                <a href="<?= base_url('go/' . $product['slug'] . '?m=' . $l['id']) ?>" target="_blank" rel="noopener nofollow sponsored"
                   class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-white font-bold shadow-cta hover:shadow-cta-lg transition <?= $s['bg'] ?>">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="text-lg leading-none shrink-0"><?= $s['icon'] ?></span>
                        <span class="flex flex-col items-start min-w-0">
                            <span class="text-sm sm:text-base leading-tight">Buy on <?= esc($l['partner_name']) ?></span>
                            <?php if ($s['tag']): ?>
                                <span class="text-[10px] font-medium opacity-80"><?= esc($s['tag']) ?></span>
                            <?php endif; ?>
                        </span>
                    </span>
                    <span class="flex items-center gap-2 shrink-0">
                        <?php if ($priceLabel): ?>
                            <span class="flex flex-col items-end leading-tight">
                                <span class="text-sm font-black"><?= $priceLabel ?></span>
                                <?php if ($age !== null && $age <= 7): ?>
                                    <span class="text-[9px] font-medium opacity-75">live price</span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($isCheapest): ?>
                            <span class="text-[10px] bg-white text-slate-900 px-1.5 py-0.5 rounded font-black">CHEAPEST</span>
                        <?php endif; ?>
                        <span class="text-base">↗</span>
                    </span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
        <p class="text-[11px] text-slate-500 text-center pt-1">
            Khoobie hand-picks these · You check out on the marketplace · Returns &amp; warranty handled by the seller
        </p>
    </div>
    <?php
    break;

case 'course':
    $course = $db->table('courses')->where('product_id', $product['id'])->get()->getRowArray() ?: [];
    $modules = $course ? $db->table('course_modules')->where('course_id', $course['id'])->orderBy('sort_order')->get()->getResultArray() : [];
    ?>
    <div class="mt-6 flex items-center gap-3">
        <a href="<?= base_url('enrol/' . $defaultVid) ?>" class="flex-1 btn-primary text-center">Enrol &amp; start watching &rarr;</a>
        <?php if (! empty($course['intro_video_url'])): ?>
            <a href="<?= esc($course['intro_video_url']) ?>" target="_blank" rel="noopener" class="btn-ghost">▶ Free preview</a>
        <?php endif; ?>
    </div>
    <?php if ($course): ?>
        <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-slate-600">
            <span>👩‍🏫 <strong><?= esc($course['instructor_name']) ?></strong></span>
            <span>🎬 <?= (int) $course['lessons_count'] ?> lessons</span>
            <span>⏱️ <?= round((int) $course['total_minutes'] / 60, 1) ?> hours</span>
            <span>🎓 Certificate <?= $course['certificate_available'] ? 'included' : '—' ?></span>
            <span>🌐 <?= esc($course['language']) ?></span>
        </div>
    <?php endif; ?>
    <?php
    break;

case 'tuition':
    $t = $db->table('tuitions')->where('product_id', $product['id'])->get()->getRowArray() ?: [];
    $days = $t ? (json_decode($t['days_of_week'] ?? '[]', true) ?: []) : [];
    $supportsTrial = ! empty($t['trial_available']);
    ?>
    <div class="mt-6 flex items-center gap-3">
        <?php if ($supportsTrial): ?>
            <a href="<?= base_url('enrol/' . $defaultVid) ?>?intent=trial" class="flex-1 btn-primary text-center">🎓 Book FREE trial &rarr;</a>
            <a href="<?= base_url('enrol/' . $defaultVid) ?>" class="btn-ghost">Enrol monthly</a>
        <?php else: ?>
            <a href="<?= base_url('enrol/' . $defaultVid) ?>" class="flex-1 btn-primary text-center">Enrol monthly &rarr;</a>
        <?php endif; ?>
    </div>
    <?php if ($t): ?>
        <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
            <div class="p-3 rounded-lg bg-slate-50"><div class="font-bold text-slate-500 uppercase tracking-wide text-[10px]">Schedule</div><div class="mt-1"><?= implode(' · ', $days) ?>, <?= substr($t['start_time'], 0, 5) ?>–<?= substr($t['end_time'], 0, 5) ?></div></div>
            <div class="p-3 rounded-lg bg-slate-50"><div class="font-bold text-slate-500 uppercase tracking-wide text-[10px]">Instructor</div><div class="mt-1"><?= esc($t['instructor_name']) ?></div></div>
            <div class="p-3 rounded-lg bg-slate-50"><div class="font-bold text-slate-500 uppercase tracking-wide text-[10px]">Modality</div><div class="mt-1 capitalize"><?= esc($t['modality']) ?></div></div>
            <div class="p-3 rounded-lg bg-slate-50"><div class="font-bold text-slate-500 uppercase tracking-wide text-[10px]">Billing</div><div class="mt-1 capitalize"><?= esc($t['billing_cycle']) ?></div></div>
        </div>
    <?php endif; ?>
    <?php
    break;

case 'meetup':
    $m = $db->table('meetups')->where('product_id', $product['id'])->get()->getRowArray() ?: [];
    $isFree = $m && $m['is_free'];
    ?>
    <div class="mt-6">
        <?php if ($isFree): ?>
            <button type="button" @click.prevent="location.href = '<?= base_url('enrol/' . ($defaultVid)) ?>'" class="w-full btn-primary">RSVP Free &rarr;</button>
        <?php else: ?>
            <button type="button" @click.prevent="location.href = '<?= base_url('enrol/' . ($defaultVid)) ?>'" class="w-full btn-primary">Reserve my spot</button>
        <?php endif; ?>
    </div>
    <?php if ($m): ?>
        <div class="mt-4 space-y-2 text-sm">
            <!-- City > Locality > Area breadcrumb -->
            <?php if (! empty($m['locality']) || ! empty($m['area'])): ?>
                <div class="flex items-center gap-1.5 text-[11px] uppercase tracking-wide font-bold text-slate-500">
                    <span class="text-base">📍</span>
                    <a href="<?= base_url('shop/local-meetups?city=' . urlencode($m['city'])) ?>" class="hover:text-brand-600"><?= esc($m['city']) ?></a>
                    <?php if (! empty($m['locality'])): ?>
                        <span class="text-slate-300">›</span>
                        <a href="<?= base_url('shop/local-meetups?city=' . urlencode($m['city']) . '&locality=' . urlencode($m['locality'])) ?>" class="hover:text-brand-600"><?= esc($m['locality']) ?></a>
                    <?php endif; ?>
                    <?php if (! empty($m['area'])): ?>
                        <span class="text-slate-300">›</span>
                        <span class="text-slate-700"><?= esc($m['area']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="flex items-start gap-2">
                <span>🏛️</span>
                <div>
                    <div class="font-semibold text-slate-900"><?= esc($m['location_name']) ?></div>
                    <div class="text-xs text-slate-500"><?= esc($m['address']) ?></div>
                    <?php if (! empty($m['pincode'])): ?>
                        <div class="text-[11px] text-slate-400">PIN <?= esc($m['pincode']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-start gap-2">
                <span>📅</span>
                <div>
                    <div class="font-semibold"><?= kb_date($m['starts_at'], true) ?></div>
                    <?php if ($m['ends_at']): ?><div class="text-xs text-slate-500">until <?= kb_date($m['ends_at'], true, 'short') ?></div><?php endif; ?>
                </div>
            </div>

            <?php if ($m['capacity']): ?>
                <div class="flex items-center gap-2">
                    <span>👥</span>
                    <div class="text-sm">Capacity <strong><?= (int) $m['capacity'] ?></strong> · <?= (int) ($m['capacity'] - $m['rsvp_count']) ?> spots left</div>
                </div>
            <?php endif; ?>

            <?php if ($m['maps_url']): ?>
                <a href="<?= esc($m['maps_url']) ?>" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand-600 font-semibold hover:underline">
                    Open in Google Maps ↗
                </a>
            <?php endif; ?>
        </div>
        <?php $agenda = json_decode($m['agenda'] ?? '[]', true) ?: []; if ($agenda): ?>
            <div class="mt-4 border-t border-slate-100 pt-3">
                <div class="text-xs uppercase tracking-wide font-bold text-slate-500">Agenda</div>
                <ul class="mt-2 space-y-1 text-sm">
                    <?php foreach ($agenda as $a): ?>
                        <li class="flex gap-3"><span class="font-mono text-slate-500 w-12"><?= esc($a['time']) ?></span><span><?= esc($a['item']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php
    break;

case 'service':
    $s = $db->table('services')->where('product_id', $product['id'])->get()->getRowArray() ?: [];
    $slots = $s ? $db->table('service_slots')
        ->where('service_id', $s['id'])
        ->where('is_booked', 0)
        ->where('starts_at >=', date('Y-m-d H:i:s'))
        ->where('starts_at <=', date('Y-m-d H:i:s', strtotime('+30 days')))
        ->orderBy('starts_at')->limit(60)->get()->getResultArray() : [];

    // Group slots by date for calendar layout
    $slotsByDate = [];
    foreach ($slots as $sl) {
        $slotsByDate[date('Y-m-d', strtotime($sl['starts_at']))][] = $sl;
    }
    ?>
    <div class="mt-6" x-data="{ selectedSlot: '', selectedLabel: '' }">
        <?php if ($slots): ?>
            <div class="text-xs font-bold uppercase tracking-wide text-slate-700 mb-2">📅 Pick a slot · next 30 days</div>
            <div class="rounded-xl border-2 border-slate-200 bg-slate-50 p-3 max-h-72 overflow-y-auto space-y-3">
                <?php foreach ($slotsByDate as $date => $daySlots): ?>
                    <div>
                        <div class="text-xs font-bold text-slate-700 mb-1.5"><?= date('l · j M', strtotime($date)) ?></div>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-1.5">
                            <?php foreach ($daySlots as $sl):
                                $ts = strtotime($sl['starts_at']);
                                $time = date('g:i A', $ts);
                                $full = date('j M, g:i A', $ts);
                            ?>
                                <button type="button"
                                        @click="selectedSlot = '<?= esc($sl['starts_at'], 'attr') ?>'; selectedLabel = '<?= esc($full, 'attr') ?>'"
                                        :class="selectedSlot === '<?= esc($sl['starts_at'], 'attr') ?>' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white border-slate-200 hover:border-brand-400'"
                                        class="text-[11px] font-semibold px-2 py-1.5 rounded-md border-2 transition">
                                    <?= esc($time) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div x-show="selectedSlot" x-cloak class="mt-2 px-3 py-1.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs">
                ✓ Slot picked: <span class="font-bold" x-text="selectedLabel"></span>
            </div>
            <button type="button" @click.prevent="location.href = '<?= base_url('enrol/' . ($defaultVid)) ?>'" :disabled="!selectedSlot"
                    class="mt-3 w-full btn-primary disabled:opacity-50">Book this slot &rarr;</button>
        <?php else: ?>
            <button type="button" @click.prevent="location.href = '<?= base_url('enrol/' . ($defaultVid)) ?>'" class="w-full btn-primary">Enquire &amp; book</button>
            <p class="mt-2 text-[11px] text-slate-500 text-center">We'll WhatsApp you to find a time that works.</p>
        <?php endif; ?>
    </div>
    <?php if ($s): ?>
        <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-600">
            <span>⏱️ <?= (int) $s['duration_minutes'] ?> min</span>
            <span>📍 <?= ucfirst(str_replace('_', ' ', $s['modality'])) ?></span>
            <?php if (! empty($s['provider_name'])): ?><span>👤 <?= esc($s['provider_name']) ?></span><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php
    break;

case 'membership':
    $mem = $db->table('memberships')->where('product_id', $product['id'])->get()->getRowArray() ?: [];
    $perks = $mem ? (json_decode($mem['perks'] ?? '[]', true) ?: []) : [];
    ?>
    <div class="mt-6 grid sm:grid-cols-2 gap-3">
        <?php if ($mem && $mem['monthly_price']): ?>
            <button type="button" @click.prevent="location.href = '<?= base_url('enrol/' . ($defaultVid)) ?>'" class="px-4 py-3 rounded-lg border-2 border-slate-200 hover:border-brand-400 bg-white">
                <div class="font-bold"><?= kb_money((int) $mem['monthly_price']) ?> <span class="text-xs text-slate-500 font-normal">/ month</span></div>
                <div class="text-xs text-slate-500">Cancel anytime</div>
            </button>
        <?php endif; ?>
        <?php if ($mem && $mem['annual_price']): ?>
            <button type="button" @click.prevent="location.href = '<?= base_url('enrol/' . ($defaultVid)) ?>'" class="px-4 py-3 rounded-lg border-2 border-brand-400 bg-brand-50">
                <div class="font-bold"><?= kb_money((int) $mem['annual_price']) ?> <span class="text-xs text-slate-500 font-normal">/ year</span></div>
                <div class="text-xs text-emerald-700 font-semibold">Best value · save ~17%</div>
            </button>
        <?php endif; ?>
    </div>
    <?php if ($perks): ?>
        <div class="mt-5">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-700">What's included</div>
            <ul class="mt-2 space-y-1.5 text-sm">
                <?php foreach ($perks as $p): ?>
                    <li class="flex gap-2"><span class="text-emerald-500 mt-0.5">✓</span><span><?= esc($p) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php
    break;

case 'digital':
    // Digital products ARE cartable (CartService::CARTABLE_TYPES). Buy & download
    // adds to cart with buyNow=true so the user lands on /cart for express checkout.
    ?>
    <div class="mt-6 flex items-center gap-3">
        <button type="button" @click="addToCart(true)" class="flex-1 btn-primary">Buy &amp; download &rarr;</button>
    </div>
    <p class="mt-2 text-xs text-slate-500 text-center">Instant download · arrives in your email + Khoobie account.</p>
    <?php
    break;

default:
    // simple / variable / bundle → default cart flow (qty stepper + Add / Buy Now).
    // Alpine pdpState.addToCart(buyNow) handles both: false stays on PDP, true → /cart.
    ?>
    <div class="mt-4 flex items-center gap-3 flex-wrap">
        <div class="flex items-center border-2 border-slate-200 rounded-lg overflow-hidden">
            <button type="button" @click="qty = Math.max(1, qty - 1)" class="px-3 py-2 text-slate-600 hover:bg-slate-100 font-bold">&minus;</button>
            <span class="w-12 text-center font-bold text-base select-none" x-text="qty">1</span>
            <button type="button" @click="qty = qty + 1" class="px-3 py-2 text-slate-600 hover:bg-slate-100 font-bold">+</button>
        </div>
        <button type="button" @click="addToCart(false)" class="flex-1 btn-ghost min-w-[120px]">Add to cart</button>
        <button type="button" @click="addToCart(true)"  class="flex-1 btn-primary min-w-[120px]">Buy Now &rarr;</button>
    </div>
    <?php
}
?>
