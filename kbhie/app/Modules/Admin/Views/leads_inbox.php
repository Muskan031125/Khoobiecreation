<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$kindLabels = [
    'trial'=>'Trial', 'rsvp'=>'RSVP', 'reserve_seat'=>'Seat reserve',
    'discovery_call'=>'Discovery call', 'notify_me'=>'Notify-me',
    'contact_instructor'=>'Contact instructor', 'enquire'=>'Enquire',
];
$statusColors = [
    'pending'   => 'bg-amber-100 text-amber-700',
    'verified'  => 'bg-sky-100 text-sky-700',
    'reserved'  => 'bg-violet-100 text-violet-700',
    'converted' => 'bg-emerald-100 text-emerald-700',
    'contacted' => 'bg-slate-100 text-slate-700',
    'cancelled' => 'bg-rose-100 text-rose-700',
    'no_show'   => 'bg-slate-200 text-slate-600',
];
?>

<div class="space-y-4">

    <div class="flex items-end justify-between">
        <div>
            <h1 class="text-2xl font-black">Lead Inbox</h1>
            <p class="text-sm text-slate-500"><?= $counts['total'] ?? 0 ?> total leads · trial signups, RSVPs, seat reservations, discovery calls</p>
        </div>
        <a href="<?= base_url('admin') ?>" class="text-sm text-slate-500 hover:underline">← Dashboard</a>
    </div>

    <!-- Filter chips -->
    <form method="get" class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
        <div class="flex flex-wrap gap-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 self-center mr-2">Kind:</span>
            <a href="?<?= http_build_query(['status' => $filters['status'], 'q' => $filters['q']]) ?>"
               class="px-2.5 py-1 rounded-full text-xs font-bold transition <?= ! $filters['kind'] ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                All (<?= $counts['total'] ?? 0 ?>)
            </a>
            <?php foreach ($kindLabels as $k => $label):
                $params = ['kind' => $k] + array_filter($filters, fn ($v) => $v !== '');
                unset($params['kind']); $params['kind'] = $k;
                $active = $filters['kind'] === $k;
            ?>
                <a href="?<?= http_build_query($params) ?>"
                   class="px-2.5 py-1 rounded-full text-xs font-bold transition <?= $active ? 'bg-brand-500 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                    <?= esc($label) ?> (<?= $counts['kind:' . $k] ?? 0 ?>)
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 self-center mr-2">Status:</span>
            <?php foreach (['pending','verified','reserved','converted','contacted','cancelled'] as $s):
                $params = array_filter($filters, fn ($v) => $v !== '');
                $params['status'] = ($filters['status'] === $s) ? '' : $s;
                $params = array_filter($params, fn ($v) => $v !== '');
                $active = $filters['status'] === $s;
            ?>
                <a href="?<?= http_build_query($params) ?>"
                   class="px-2.5 py-1 rounded-full text-xs font-bold transition <?= $active ? 'bg-slate-900 text-white' : ($statusColors[$s] ?? 'bg-slate-100 text-slate-700') . ' hover:opacity-80' ?>">
                    <?= ucfirst($s) ?> (<?= $counts['status:' . $s] ?? 0 ?>)
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex gap-2">
            <input name="q" placeholder="Search by name / phone / email / product" value="<?= esc($filters['q']) ?>" class="flex-1 px-3 py-2 rounded-lg border-2 border-slate-200 text-sm">
            <?php foreach (['kind' => $filters['kind'], 'status' => $filters['status']] as $k => $v): ?>
                <?php if ($v): ?><input type="hidden" name="<?= $k ?>" value="<?= esc($v) ?>"><?php endif; ?>
            <?php endforeach; ?>
            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-bold">Search</button>
        </div>
    </form>

    <?= view('App\Modules\Admin\Views\_bulk_toolbar', [
        'table' => 'intents',
        'ids'   => array_map(fn ($i) => (int) $i['id'], $intents),
        'actions' => [
            ['key' => 'contacted', 'label' => '✓ Contacted', 'cls' => 'bg-emerald-500'],
            ['key' => 'cancelled', 'label' => '✕ Cancelled', 'cls' => 'bg-rose-500'],
        ],
    ]) ?>

    <!-- Lead table -->
    <div x-data="bulk({table:'intents', rows:<?= json_encode(array_map(fn ($i) => (int) $i['id'], $intents)) ?>})" class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
                <tr>
                    <th class="text-left p-3 w-8"><input type="checkbox" @click="toggleAll($event.target.checked)" :checked="selected.length === rows.length && rows.length > 0"></th>
                    <th class="text-left p-3">When</th>
                    <th class="text-left p-3">Kind</th>
                    <th class="text-left p-3">Contact</th>
                    <th class="text-left p-3">Product</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-left p-3">Pay</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($intents)): ?>
                    <tr><td colspan="8" class="p-8 text-center text-slate-500">No leads match.</td></tr>
                <?php endif; ?>
                <?php foreach ($intents as $r): ?>
                    <tr class="border-b last:border-0 hover:bg-slate-50" :class="selected.includes(<?= (int) $r['id'] ?>) ? 'bg-brand-50' : ''">
                        <td class="p-3 align-top"><input type="checkbox" :value="<?= (int) $r['id'] ?>" x-model="selected"></td>
                        <td class="p-3 align-top">
                            <div class="text-xs font-semibold"><?= date('j M', strtotime($r['created_at'])) ?></div>
                            <div class="text-[10px] text-slate-500"><?= date('g:i A', strtotime($r['created_at'])) ?></div>
                        </td>
                        <td class="p-3 align-top">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-violet-100 text-violet-700"><?= esc($kindLabels[$r['kind']] ?? $r['kind']) ?></span>
                        </td>
                        <td class="p-3 align-top">
                            <div class="font-semibold"><?= esc($r['name'] ?: '—') ?><?= $r['child_age'] ? ' <span class="text-xs text-slate-500">· child age ' . (int) $r['child_age'] . '</span>' : '' ?></div>
                            <div class="text-xs text-slate-500"><?= esc($r['phone'] ?: $r['email']) ?></div>
                            <?php if ($r['phone']): ?>
                                <a href="https://wa.me/91<?= ltrim($r['phone'], '+91') ?>?text=<?= urlencode('Hi! Khoobie here. Following up on your enquiry.') ?>" target="_blank" class="inline-block mt-0.5 text-[10px] text-emerald-700 hover:underline">📱 WhatsApp →</a>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 align-top">
                            <?php if ($r['product_name']): ?>
                                <a href="<?= base_url('product/' . $r['product_slug']) ?>" target="_blank" class="font-semibold text-brand-600 hover:underline line-clamp-2 max-w-xs"><?= esc($r['product_name']) ?></a>
                                <div class="text-[10px] text-slate-500 capitalize"><?= esc($r['product_type']) ?></div>
                            <?php endif; ?>
                            <?php if ($r['preferred_slot']): ?>
                                <div class="text-xs text-slate-600 mt-0.5">🕒 <?= esc($r['preferred_slot']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 align-top">
                            <span class="px-2 py-0.5 rounded text-xs font-bold <?= $statusColors[$r['status']] ?? 'bg-slate-100 text-slate-700' ?>"><?= esc($r['status']) ?></span>
                            <?php if ($r['verified_at']): ?>
                                <div class="text-[10px] text-emerald-600 mt-0.5">✓ OTP verified</div>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 align-top">
                            <?php if ($r['amount_due']): ?>
                                <div class="text-xs">
                                    <strong>₹<?= number_format(round($r['amount_paid'] / 100)) ?></strong>
                                    <span class="text-slate-400">/ ₹<?= number_format(round($r['amount_due'] / 100)) ?></span>
                                </div>
                                <?php if ($r['amount_paid'] >= $r['amount_due']): ?>
                                    <div class="text-[10px] text-emerald-700">paid</div>
                                <?php else: ?>
                                    <div class="text-[10px] text-amber-700">advance ₹<?= number_format(round($r['amount_paid'] / 100)) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 align-top text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <?php if (! in_array($r['status'], ['converted','contacted'])): ?>
                                    <form method="post" action="<?= base_url('admin/leads/' . $r['id'] . '/status') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="contacted">
                                        <button class="px-2 py-1 rounded bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-[10px] font-bold">✓ Contacted</button>
                                    </form>
                                <?php endif; ?>
                                <a href="<?= base_url('admin/leads/' . $r['id']) ?>" class="text-xs text-brand-600 font-bold hover:underline">View →</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
