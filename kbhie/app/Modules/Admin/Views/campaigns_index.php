<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="flex items-end justify-between mb-4">
    <div>
        <h1 class="text-2xl font-black">Marketing Campaigns</h1>
        <p class="text-sm text-slate-500">Email · WhatsApp · SMS · AI-drafted broadcasts</p>
    </div>
    <a href="<?= base_url('admin/campaigns/new') ?>" class="btn-primary">+ New campaign</a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
            <tr><th class="text-left p-3">Name</th><th class="text-left p-3">Channel</th><th class="text-left p-3">Audience</th><th class="text-right p-3">Sent</th><th class="text-left p-3">Status</th><th class="text-left p-3">Updated</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="p-8 text-center text-slate-400">No campaigns yet. <a href="<?= base_url('admin/campaigns/new') ?>" class="text-brand-600 font-bold">Create one →</a></td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr class="border-b last:border-0 hover:bg-slate-50">
                    <td class="p-3">
                        <div class="font-bold"><?= esc($r['name']) ?></div>
                        <?php if ($r['ai_generated']): ?><span class="text-[10px] font-bold bg-violet-100 text-violet-700 px-1.5 py-0.5 rounded">✨ AI</span><?php endif; ?>
                    </td>
                    <td class="p-3 capitalize"><?= esc($r['channel']) ?></td>
                    <td class="p-3 text-xs text-slate-600"><?= esc(str_replace('_',' ', $r['audience'])) ?><?= $r['audience_arg'] ? ': ' . esc($r['audience_arg']) : '' ?></td>
                    <td class="p-3 text-right tabular-nums"><?= number_format($r['recipients_n']) ?></td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-bold <?= $r['status']==='sent'?'bg-emerald-100 text-emerald-700':($r['status']==='sending'?'bg-amber-100 text-amber-700':'bg-slate-100 text-slate-700') ?>"><?= esc($r['status']) ?></span></td>
                    <td class="p-3 text-xs text-slate-500"><?= date('j M, g:i A', strtotime($r['updated_at'])) ?></td>
                    <td class="p-3 text-right"><a href="<?= base_url('admin/campaigns/' . $r['id'] . '/edit') ?>" class="text-brand-600 font-bold text-xs hover:underline">Edit →</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
