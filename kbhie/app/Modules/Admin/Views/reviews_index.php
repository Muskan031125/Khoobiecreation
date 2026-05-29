<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="flex items-end justify-between mb-4">
    <div>
        <h1 class="text-2xl font-black">Reviews Moderation</h1>
        <p class="text-sm text-slate-500"><?= $counts['pending'] ?? 0 ?> pending review · <?= $counts['published'] ?? 0 ?> published</p>
    </div>
</div>

<div class="flex flex-wrap gap-1.5 mb-4">
    <?php foreach (['pending'=>'amber','published'=>'emerald','rejected'=>'rose'] as $s => $color): ?>
        <a href="?status=<?= $s ?>" class="px-3 py-1 rounded-full text-xs font-bold <?= $status === $s ? 'bg-slate-900 text-white' : "bg-{$color}-100 text-{$color}-700 hover:opacity-80" ?>">
            <?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)
        </a>
    <?php endforeach; ?>
</div>

<div class="space-y-3">
    <?php if (empty($rows)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-8 text-center text-slate-400">No <?= esc($status) ?> reviews.</div>
    <?php endif; ?>

    <?php foreach ($rows as $r): ?>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-500 font-bold text-sm">
                            <?php for ($i = 1; $i <= 5; $i++): ?><?= $i <= (int) $r['rating'] ? '★' : '☆' ?><?php endfor; ?>
                        </span>
                        <span class="text-xs text-slate-500">by <strong><?= esc($r['reviewer_name'] ?: 'Anonymous') ?></strong> · <?= date('j M Y', strtotime($r['created_at'])) ?></span>
                        <?php if ($r['is_verified_buyer']): ?><span class="text-[10px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded">✓ Verified buyer</span><?php endif; ?>
                    </div>
                    <a href="<?= base_url('product/' . $r['product_slug']) ?>" target="_blank" class="block mt-1 font-bold text-brand-600 hover:underline text-sm"><?= esc($r['product_name']) ?> ↗</a>
                    <?php if (! empty($r['title'])): ?>
                        <div class="mt-2 font-display font-black"><?= esc($r['title']) ?></div>
                    <?php endif; ?>
                    <p class="mt-1 text-sm text-slate-700 whitespace-pre-wrap"><?= esc($r['body']) ?></p>
                </div>
                <?php if ($status === 'pending'): ?>
                    <div class="flex gap-2 shrink-0">
                        <form method="post" action="<?= base_url('admin/reviews/' . $r['id'] . '/approve') ?>"><?= csrf_field() ?>
                            <button class="px-3 py-1.5 rounded-md bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold">✓ Approve</button>
                        </form>
                        <form method="post" action="<?= base_url('admin/reviews/' . $r['id'] . '/reject') ?>"><?= csrf_field() ?>
                            <button class="px-3 py-1.5 rounded-md bg-rose-500 hover:bg-rose-600 text-white text-xs font-bold">✕ Reject</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
