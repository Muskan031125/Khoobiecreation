<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<section class="py-6 sm:py-10 lg:py-12 bg-slate-50 min-h-[60vh]">
    <div class="mx-auto max-w-4xl px-3 sm:px-4 lg:px-6">

        <nav class="text-[11px] sm:text-xs text-slate-500 flex flex-wrap items-center gap-x-1 mb-2">
            <a href="<?= base_url('/') ?>" class="hover:underline">Home</a> <span>&raquo;</span>
            <a href="<?= base_url('account') ?>" class="hover:underline">My account</a> <span>&raquo;</span>
            <span class="text-slate-900 font-semibold">Refer a friend</span>
        </nav>

        <span class="eyebrow text-brand-600">🎁 Refer & earn</span>
        <h1 class="h-display text-2xl sm:text-3xl lg:text-4xl mt-1 text-slate-900">Share Khoobie, earn rewards</h1>
        <p class="text-sm text-slate-600 mt-1 max-w-xl">Every friend who signs up with your link and places their first order earns you <strong><?= (int) $ref['reward_amount'] ?> Khoobie points</strong>, and gets <strong>10% off</strong> using <code class="font-mono bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded text-xs"><?= esc($ref['referee_coupon']) ?></code>.</p>

        <!-- ===== Big share card ===== -->
        <div class="mt-6 bg-gradient-to-br from-brand-500 via-rose-500 to-amber-500 rounded-3xl p-6 sm:p-8 text-white shadow-cta-lg">
            <div class="text-xs uppercase tracking-wider font-bold opacity-80">Your unique link</div>
            <div class="mt-2 text-2xl sm:text-3xl font-display font-black break-all"><?= esc($ref['link']) ?></div>

            <div class="mt-4 flex flex-wrap gap-2"
                 x-data="{ copied: false }">
                <button type="button"
                        @click="navigator.clipboard.writeText('<?= esc($ref['link'], 'attr') ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-4 py-2.5 rounded-full bg-white text-slate-900 font-bold text-sm hover:bg-slate-100 transition">
                    <span x-show="!copied">📋 Copy link</span>
                    <span x-show="copied" x-cloak>✓ Copied!</span>
                </button>
                <a href="https://wa.me/?text=<?= urlencode("Hey! Try Khoobie for screen-free kids' fun. Use my link: " . $ref['link']) ?>" target="_blank"
                   class="px-4 py-2.5 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm transition">
                    💬 Share on WhatsApp
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($ref['link']) ?>" target="_blank"
                   class="px-4 py-2.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm transition">
                    📘 Share on Facebook
                </a>
                <a href="mailto:?subject=<?= rawurlencode('Check out Khoobie') ?>&body=<?= rawurlencode("I've been using Khoobie for hands-on, screen-free fun for the kids — thought you'd love it too.\n\n" . $ref['link']) ?>"
                   class="px-4 py-2.5 rounded-full bg-white/15 hover:bg-white/25 text-white font-bold text-sm border border-white/20 transition">
                    📧 Email
                </a>
            </div>
        </div>

        <!-- ===== Stats grid ===== -->
        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl p-4 shadow-soft">
                <div class="text-[10px] uppercase tracking-wider font-bold text-slate-500">Link clicks</div>
                <div class="mt-1 text-2xl font-display font-black text-slate-900 tabular-nums"><?= (int) $ref['total_clicks'] ?></div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-soft">
                <div class="text-[10px] uppercase tracking-wider font-bold text-slate-500">Friends signed up</div>
                <div class="mt-1 text-2xl font-display font-black text-slate-900 tabular-nums"><?= (int) $ref['signed_up'] ?></div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-soft">
                <div class="text-[10px] uppercase tracking-wider font-bold text-slate-500">First orders placed</div>
                <div class="mt-1 text-2xl font-display font-black text-emerald-600 tabular-nums"><?= (int) $ref['converted'] ?></div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-soft border-2 border-amber-300">
                <div class="text-[10px] uppercase tracking-wider font-bold text-amber-700">Points earned</div>
                <div class="mt-1 text-2xl font-display font-black text-amber-700 tabular-nums">★ <?= (int) $ref['points_earned'] ?></div>
            </div>
        </div>

        <!-- ===== How it works ===== -->
        <div class="mt-8 bg-white rounded-2xl p-6 shadow-soft">
            <h2 class="font-display font-black text-lg">How it works</h2>
            <ol class="mt-3 space-y-3">
                <li class="flex items-start gap-3">
                    <span class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-brand-500 text-white text-xs font-black">1</span>
                    <div><strong>Share your link</strong> on WhatsApp, Instagram, or by email.</div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-brand-500 text-white text-xs font-black">2</span>
                    <div>When your friend clicks, they save <strong>10% off</strong> on their first order using the auto-applied <code class="font-mono bg-amber-100 text-amber-800 px-1 rounded text-xs"><?= esc($ref['referee_coupon']) ?></code> code.</div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-brand-500 text-white text-xs font-black">3</span>
                    <div>When they place that first order, you get <strong>★ <?= (int) $ref['reward_amount'] ?> Khoobie points</strong> — usable as ₹<?= (int) ($ref['reward_amount'] / 2) ?> off your next purchase.</div>
                </li>
            </ol>
        </div>

        <!-- ===== Pro tips ===== -->
        <div class="mt-6 bg-amber-50 border-2 border-dashed border-amber-200 rounded-2xl p-4 sm:p-6">
            <h3 class="font-display font-black text-base">💡 Pro tips for maximum referrals</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-700 list-disc ml-5">
                <li>Share in your kid's school WhatsApp group when a new class starts</li>
                <li>Post a photo of your child using a Khoobie kit + your link</li>
                <li>Send it to parents who post on Instagram about their kid's activities</li>
                <li>Mention it in birthday party group chats — your link works on Return Gifts too</li>
            </ul>
        </div>

    </div>
</section>

<?= $this->endSection() ?>
