<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="flex items-end justify-between mb-4">
    <div>
        <h1 class="text-2xl font-black">Blog posts</h1>
        <p class="text-sm text-slate-500 mt-1">AI-drafted, human-reviewed. <?= count($rows) ?> total.</p>
    </div>
    <a href="<?= base_url('admin/blogs/new') ?>" class="btn-primary">+ New post</a>
</div>

<?= view('App\Modules\Admin\Views\_bulk_toolbar', [
    'table' => 'blogs',
    'ids'   => array_map(fn ($r) => (int) $r['id'], $rows),
    'actions' => [
        ['key' => 'publish', 'label' => '🚀 Publish', 'cls' => 'bg-emerald-500'],
        ['key' => 'archive', 'label' => '📦 Archive', 'cls' => 'bg-slate-500'],
    ],
]) ?>

<div x-data="bulk({table:'blogs', rows:<?= json_encode(array_map(fn ($r) => (int) $r['id'], $rows)) ?>})" class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase tracking-wider text-slate-500 bg-slate-50">
            <tr><th class="text-left p-3 w-8"><input type="checkbox" @click="toggleAll($event.target.checked)" :checked="selected.length === rows.length && rows.length > 0"></th><th class="text-left p-3">Title</th><th class="text-left p-3">Status</th><th class="text-left p-3">AI</th><th class="text-left p-3">Views</th><th class="text-left p-3">Updated</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr class="border-b last:border-0 hover:bg-slate-50" :class="selected.includes(<?= (int) $r['id'] ?>) ? 'bg-brand-50' : ''">
                    <td class="p-3"><input type="checkbox" :value="<?= (int) $r['id'] ?>" x-model="selected"></td>
                    <td class="p-3">
                        <div class="font-bold"><?= esc($r['title']) ?></div>
                        <div class="text-xs text-slate-500 font-mono"><?= esc($r['slug']) ?></div>
                    </td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-bold <?= $r['status']==='published'?'bg-emerald-100 text-emerald-700':($r['status']==='draft'?'bg-amber-100 text-amber-700':'bg-slate-100 text-slate-700') ?>"><?= esc($r['status']) ?></span></td>
                    <td class="p-3"><?= $r['ai_generated'] ? '<span class="px-2 py-0.5 rounded text-xs font-bold bg-violet-100 text-violet-700">✨ AI</span>' : '<span class="text-xs text-slate-400">human</span>' ?></td>
                    <td class="p-3 tabular-nums"><?= number_format($r['views_count']) ?></td>
                    <td class="p-3 text-xs text-slate-500"><?= date('j M, g:i A', strtotime($r['updated_at'])) ?></td>
                    <td class="p-3 text-right"><a href="<?= base_url('admin/blogs/' . $r['id'] . '/edit') ?>" class="text-brand-600 font-bold hover:underline">Edit →</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
