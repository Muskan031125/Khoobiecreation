<?php
/**
 * Product Q&A block — appears below related products on PDP.
 * Shows answered questions + form for new ones.
 */
$published = array_filter($product['questions'] ?? [], fn ($q) => ! empty($q['is_published']));
?>
<section class="py-10 bg-slate-50 border-t border-slate-100">
    <div class="mx-auto max-w-3xl px-3 sm:px-4 lg:px-6">
        <div class="flex items-end justify-between gap-3 flex-wrap mb-4">
            <div>
                <span class="eyebrow text-brand-600">💬 Questions & Answers</span>
                <h2 class="h-display text-2xl sm:text-3xl mt-1">Got a question?</h2>
                <p class="text-sm text-slate-500 mt-1">Other parents and the Khoobie team answer within 24 hours.</p>
            </div>
        </div>

        <!-- Answered Q&A -->
        <?php if (! empty($published)): ?>
            <div class="space-y-3">
                <?php foreach (array_slice($published, 0, 5) as $q): ?>
                    <details class="bg-white rounded-2xl shadow-soft p-4 group">
                        <summary class="cursor-pointer flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="font-bold text-slate-900 leading-snug">Q: <?= esc($q['question']) ?></div>
                                <?php if (! empty($q['asker_name'])): ?>
                                    <div class="text-[11px] text-slate-500 mt-0.5">— <?= esc($q['asker_name']) ?> · <?= date('j M Y', strtotime($q['created_at'])) ?></div>
                                <?php endif; ?>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <?php if (! empty($q['answer'])): ?>
                            <div class="mt-3 pt-3 border-t border-slate-100">
                                <div class="text-xs uppercase tracking-wider font-bold text-emerald-600 mb-1">Answer</div>
                                <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(esc($q['answer'])) ?></p>
                                <?php if (! empty($q['answered_at'])): ?>
                                    <div class="mt-2 text-[10px] text-slate-400">Answered <?= date('j M Y', strtotime($q['answered_at'])) ?> by Khoobie</div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-soft p-6 text-center text-sm text-slate-500">
                Be the first to ask a question about this product!
            </div>
        <?php endif; ?>

        <!-- Ask form -->
        <div class="mt-4 bg-white rounded-2xl shadow-soft p-5">
            <h3 class="font-display font-black">Ask a question</h3>
            <form method="post" action="<?= base_url('product/' . $product['slug'] . '/question') ?>" class="mt-3 space-y-3">
                <?= csrf_field() ?>
                <input name="asker_name" placeholder="Your name (optional)" value="<?= esc(session('user')['name'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none text-sm">
                <textarea name="question" required rows="3" placeholder="What would you like to know? — e.g. is this suitable for a 6-year-old? what's in the box? how long does it take?" class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 focus:border-brand-400 outline-none text-sm"></textarea>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[11px] text-slate-500">We'll email you when answered.</p>
                    <button type="submit" class="px-5 py-2 rounded-full bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold transition">Send question</button>
                </div>
            </form>
        </div>
    </div>
</section>
