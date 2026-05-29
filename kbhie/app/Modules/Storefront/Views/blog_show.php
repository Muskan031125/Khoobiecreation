<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<!-- Article JSON-LD for AEO/GEO + Google rich results -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "<?= esc($row['title']) ?>",
  "datePublished": "<?= date('c', strtotime($row['published_at'] ?: $row['created_at'])) ?>",
  "author": { "@type": "Person", "name": "<?= esc($row['author_name']) ?>" },
  "publisher": { "@type": "Organization", "name": "Krafty Khoobie", "logo": { "@type": "ImageObject", "url": "<?= base_url('assets/brand/logo.png') ?>" } },
  "image": "<?= esc($row['hero_image'] ?: base_url('assets/og-default.jpg')) ?>",
  "mainEntityOfPage": "<?= current_url() ?>"
}
</script>

<article class="py-8 sm:py-12 lg:py-16 bg-white">
    <div class="mx-auto max-w-3xl px-3 sm:px-4 lg:px-6">

        <nav class="text-[11px] sm:text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-3">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <a href="<?= base_url('blog') ?>" class="hover:underline">Blog</a> <span>&raquo;</span>
            <span class="text-slate-900 font-semibold line-clamp-1"><?= esc($row['title']) ?></span>
        </nav>

        <header class="mb-6">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <?php if ($row['ai_generated']): ?><span class="px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 font-bold">✨ AI-drafted, human-reviewed</span><?php endif; ?>
                <span><?= date('j M Y', strtotime($row['published_at'] ?: $row['created_at'])) ?></span>
                <span>·</span>
                <span><?= number_format($row['views_count']) ?> reads</span>
            </div>
            <h1 class="mt-2 h-display text-3xl sm:text-4xl lg:text-5xl text-slate-900"><?= esc($row['title']) ?></h1>
            <?php if (! empty($row['excerpt'])): ?>
                <p class="mt-3 text-lg text-slate-600 leading-relaxed"><?= esc($row['excerpt']) ?></p>
            <?php endif; ?>
            <div class="mt-4 text-sm text-slate-500">By <strong class="text-slate-900"><?= esc($row['author_name']) ?></strong></div>
        </header>

        <?php if (! empty($row['hero_image'])): ?>
            <img src="<?= esc($row['hero_image']) ?>" alt="<?= esc($row['title']) ?>" class="w-full aspect-[16/9] object-cover rounded-2xl mb-6">
        <?php endif; ?>

        <div class="prose prose-slate max-w-none prose-headings:font-display prose-headings:font-black prose-h2:text-2xl prose-h3:text-xl prose-h2:mt-8 prose-h3:mt-6 prose-a:text-brand-600 prose-li:my-1">
            <?= $html ?>
        </div>

        <div class="mt-10 pt-6 border-t border-slate-200 flex flex-wrap gap-3 items-center">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Share</span>
            <?php $u = urlencode(current_url()); $t = urlencode($row['title']); ?>
            <a href="https://wa.me/?text=<?= $t ?>%20<?= $u ?>" target="_blank" class="px-3 py-1.5 rounded-full bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-bold">WhatsApp</a>
            <a href="https://twitter.com/intent/tweet?text=<?= $t ?>&url=<?= $u ?>" target="_blank" class="px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold">X / Twitter</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $u ?>" target="_blank" class="px-3 py-1.5 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs font-bold">Facebook</a>
        </div>
    </div>
</article>

<?php if (! empty($related)): ?>
<section class="py-10 bg-slate-50 border-t border-slate-100">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 lg:px-6">
        <h2 class="font-display text-xl sm:text-2xl font-black">Keep reading</h2>
        <div class="mt-4 grid sm:grid-cols-3 gap-4">
            <?php foreach ($related as $r): ?>
                <a href="<?= base_url('blog/' . $r['slug']) ?>" class="bg-white rounded-xl p-4 ring-1 ring-slate-100 hover:ring-brand-200 transition">
                    <div class="text-xs text-slate-500"><?= date('j M Y', strtotime($r['published_at'] ?: $r['created_at'])) ?></div>
                    <h3 class="mt-1 font-display font-black line-clamp-2"><?= esc($r['title']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?= $this->endSection() ?>
