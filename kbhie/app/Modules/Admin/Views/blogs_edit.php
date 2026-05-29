<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<form method="post" action="<?= base_url('admin/blogs' . ($row ? '/' . $row['id'] : '')) ?>" x-data="blogEditor()" class="grid lg:grid-cols-[1fr_320px] gap-6 max-w-7xl">
    <?= csrf_field() ?>
    <?php if ($row): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <div class="space-y-4">
        <!-- AI generator box -->
        <div class="bg-violet-50 border-2 border-dashed border-violet-300 rounded-2xl p-5 space-y-3">
            <div class="flex items-center gap-2">
                <span class="text-2xl">✨</span>
                <div>
                    <h2 class="font-bold text-violet-900">AI Blog Drafter</h2>
                    <p class="text-xs text-violet-700">Give a topic + keywords, get a 600-1500 word draft in 10 seconds.</p>
                </div>
            </div>
            <input type="text" x-model="topic" placeholder="Topic — e.g. 'Best return gifts for kids' birthday parties in Mumbai'" class="w-full px-3 py-2 rounded-lg border-2 border-violet-200 focus:border-violet-500 outline-none">
            <input type="text" x-model="keywords" placeholder="Target SEO keywords (comma-separated)" class="w-full px-3 py-2 rounded-lg border-2 border-violet-200 focus:border-violet-500 outline-none">
            <div class="flex gap-2 items-center">
                <select x-model.number="words" class="px-3 py-2 rounded-lg border-2 border-violet-200 text-sm">
                    <option value="500">500 words (quick read)</option>
                    <option value="800">800 words (standard)</option>
                    <option value="1200">1,200 words (in-depth)</option>
                    <option value="2000">2,000 words (pillar)</option>
                </select>
                <button type="button" @click="generate()" :disabled="busy || ! topic"
                        class="ml-auto px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm shadow-sm disabled:opacity-50 transition">
                    <span x-show="!busy">✨ Generate draft</span>
                    <span x-show="busy" x-cloak>🤖 Writing…</span>
                </button>
            </div>
            <p x-show="status" x-cloak class="text-xs text-violet-800" x-text="status"></p>
        </div>

        <!-- Basics -->
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">Post</h2>
            <input name="title" x-model="title" required placeholder="Post title" value="<?= esc($row['title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-lg font-bold">
            <input name="slug" placeholder="URL slug (auto from title)" value="<?= esc($row['slug'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-mono text-sm">
            <textarea name="excerpt" rows="2" placeholder="1-2 sentence excerpt for listing pages"
                      class="w-full px-3 py-2 rounded-lg border border-slate-200"><?= esc($row['excerpt'] ?? '') ?></textarea>
            <textarea name="body_md" x-model="bodyMd" rows="22" placeholder="Body (Markdown)" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-mono text-sm"><?= esc($row['body_md'] ?? '') ?></textarea>
        </div>

        <!-- SEO meta -->
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">SEO meta</h2>
            <input name="seo_title" x-model="seoTitle" maxlength="80" placeholder="SEO title (≤60 chars)" value="<?= esc($row['seo_title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200">
            <textarea name="seo_description" x-model="seoDesc" rows="3" maxlength="200" placeholder="Meta description (≤160 chars)" class="w-full px-3 py-2 rounded-lg border border-slate-200"><?= esc($row['seo_description'] ?? '') ?></textarea>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-bold">Publish</h2>
            <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200">
                <?php foreach (['draft','published','archived'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($row['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="author_name" value="<?= esc($row['author_name'] ?? 'Khoobie Editorial') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Author">
            <input name="hero_image" value="<?= esc($row['hero_image'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Hero image URL">
            <input name="tags" value="<?= esc($row['tags'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Tags (comma-separated)">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="ai_generated" value="1" x-model="aiGenerated" <?= !empty($row['ai_generated']) ? 'checked' : '' ?>>
                <span>AI-generated</span>
            </label>
            <button type="submit" class="w-full btn-primary">Save</button>
            <?php if ($row): ?><a href="<?= base_url('blog/' . $row['slug']) ?>" target="_blank" class="block text-center text-xs text-slate-500 hover:underline">View on storefront →</a><?php endif; ?>
        </div>
    </aside>
</form>

<script>
function blogEditor() {
    return {
        topic: '', keywords: '', words: 800, busy: false, status: '',
        title: '<?= esc($row['title'] ?? '', 'js') ?>',
        bodyMd: <?= json_encode($row['body_md'] ?? '') ?>,
        seoTitle: '<?= esc($row['seo_title'] ?? '', 'js') ?>',
        seoDesc:  '<?= esc($row['seo_description'] ?? '', 'js') ?>',
        aiGenerated: <?= !empty($row['ai_generated']) ? 'true' : 'false' ?>,
        async generate() {
            this.busy = true; this.status = 'Calling LLM…';
            try {
                const fd = new FormData();
                fd.append('topic', this.topic); fd.append('keywords', this.keywords); fd.append('words', this.words);
                fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                const r = await fetch('<?= base_url('admin/ai/blog-draft') ?>', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.ok) {
                    this.title    = j.title    || this.topic;
                    this.bodyMd   = j.body_md  || '';
                    this.seoTitle = j.title    || '';
                    this.seoDesc  = j.meta_desc|| '';
                    this.aiGenerated = true;
                    this.status = '✓ Draft ready. Review and save.';
                } else {
                    this.status = '⚠ ' + (j.error || 'Failed');
                }
            } catch (e) { this.status = '⚠ Network error'; }
            this.busy = false;
        }
    }
}
</script>

<?= $this->endSection() ?>
