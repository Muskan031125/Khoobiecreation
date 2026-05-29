<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-8 sm:py-12 lg:py-16 bg-gradient-to-b from-white to-slate-50">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 lg:px-6">
        <span class="eyebrow text-brand-600">📖 The Khoobie Blog</span>
        <h1 class="h-display text-3xl sm:text-4xl lg:text-5xl mt-1 text-slate-900">Screen-free parenting ideas, by parents</h1>
        <p class="text-sm sm:text-base text-slate-600 mt-2 max-w-2xl">Practical, India-rooted writing for raising curious, off-screen kids — sourced from real parents, expert instructors, and the Khoobie editorial team.</p>
    </div>
</section>

<section class="py-6 sm:py-10 bg-slate-50 min-h-[40vh]">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 lg:px-6">
        <?php if (empty($rows)): ?>
            <div class="bg-white rounded-2xl p-10 text-center">
                <p class="text-slate-500">No posts yet. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-2 gap-4 sm:gap-6">
                <?php foreach ($rows as $r): ?>
                    <a href="<?= base_url('blog/' . $r['slug']) ?>" class="group block bg-white rounded-2xl overflow-hidden ring-1 ring-slate-100 hover:ring-brand-200 hover:shadow-soft-lg transition">
                        <?php if (! empty($r['hero_image'])): ?>
                            <div class="aspect-[16/9] bg-slate-100 overflow-hidden">
                                <img src="<?= esc($r['hero_image']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            </div>
                        <?php endif; ?>
                        <div class="p-5">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <?php if ($r['ai_generated']): ?><span class="px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 font-bold">✨ AI-drafted</span><?php endif; ?>
                                <span><?= date('j M Y', strtotime($r['published_at'] ?: $r['created_at'])) ?></span>
                                <span>·</span>
                                <span><?= number_format($r['views_count']) ?> reads</span>
                            </div>
                            <h2 class="mt-2 font-display text-xl font-black text-slate-900 group-hover:text-brand-600 line-clamp-2"><?= esc($r['title']) ?></h2>
                            <?php if (! empty($r['excerpt'])): ?>
                                <p class="mt-1 text-sm text-slate-600 line-clamp-3"><?= esc($r['excerpt']) ?></p>
                            <?php endif; ?>
                            <div class="mt-3 text-xs font-bold text-brand-600">Read more →</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
