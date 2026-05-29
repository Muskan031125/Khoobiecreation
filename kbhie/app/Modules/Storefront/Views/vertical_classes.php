<?= $this->extend('layouts/master') ?>
<?= $this->section('content') ?>

<!-- Course/Education JSON-LD for AEO/GEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "name": "Krafty Khoobie Classes",
  "url": "<?= current_url() ?>",
  "areaServed": "IN",
  "offers": {
    "@type": "AggregateOffer",
    "priceCurrency": "INR",
    "lowPrice": "500",
    "highPrice": "5000",
    "offerCount": <?= count($tuitions) + count($courses) ?>
  }
}
</script>

<section class="relative overflow-hidden bg-gradient-to-br from-violet-50 via-sky-50 to-emerald-50 py-10 sm:py-16 lg:py-20">
    <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-violet-200/40 blur-3xl"></div>
    <div class="absolute -bottom-20 -left-20 w-72 h-72 rounded-full bg-sky-200/40 blur-3xl"></div>
    <div class="relative mx-auto max-w-5xl px-3 sm:px-4 lg:px-6 text-center">
        <span class="eyebrow text-violet-700">🎓 Live & on-demand</span>
        <h1 class="h-display text-3xl sm:text-5xl lg:text-6xl mt-2 text-slate-900">Classes kids actually look forward to</h1>
        <p class="mt-3 text-base sm:text-lg text-slate-700 max-w-2xl mx-auto">From chess + abacus + Vedic maths to calligraphy + storytelling + classical music — 100+ live online and recorded classes, all with free trial.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <a href="<?= base_url('shop/mindsport-classes') ?>" class="px-4 py-2 rounded-full bg-white hover:bg-slate-50 text-slate-900 font-bold text-sm shadow-sm">♟️ Mind sports</a>
            <a href="<?= base_url('shop/creative-classes') ?>" class="px-4 py-2 rounded-full bg-white hover:bg-slate-50 text-slate-900 font-bold text-sm shadow-sm">🎨 Creative</a>
            <a href="<?= base_url('shop/activity-classes') ?>" class="px-4 py-2 rounded-full bg-white hover:bg-slate-50 text-slate-900 font-bold text-sm shadow-sm">🎤 Activity & confidence</a>
        </div>
    </div>
</section>

<section class="py-8 sm:py-12 bg-white">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <div class="flex items-end justify-between flex-wrap gap-2">
            <div>
                <span class="eyebrow text-brand-600">🔥 Most-loved live classes</span>
                <h2 class="h-display text-2xl sm:text-3xl mt-1">Live tuitions with vetted teachers</h2>
            </div>
            <a href="<?= base_url('shop/classes') ?>" class="text-sm font-bold text-brand-600 hover:underline">All classes →</a>
        </div>
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php foreach (array_slice($tuitions, 0, 8) as $p): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p, 'cartVariants'=>$cartVariants??[], 'shortlistIds'=>$shortlistIds??[], 'compareIds'=>$compareIds??[]]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-8 sm:py-12 bg-slate-50">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 lg:px-6">
        <div class="flex items-end justify-between flex-wrap gap-2">
            <div>
                <span class="eyebrow text-emerald-700">🎬 Watch anytime</span>
                <h2 class="h-display text-2xl sm:text-3xl mt-1">Self-paced video courses</h2>
                <p class="text-sm text-slate-500 mt-1">Lifetime access · certificate · 7-day refund</p>
            </div>
            <a href="<?= base_url('shop/creative-classes') ?>?type=course" class="text-sm font-bold text-emerald-700 hover:underline">Browse courses →</a>
        </div>
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <?php foreach ($courses as $p): ?>
                <?= view('App\Modules\Storefront\Views\_product_card', ['p' => $p, 'cartVariants'=>$cartVariants??[], 'shortlistIds'=>$shortlistIds??[], 'compareIds'=>$compareIds??[]]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FAQ — directly indexed by Google + answer-cited by AI search engines -->
<section class="py-10 bg-white">
    <div class="mx-auto max-w-3xl px-3 sm:px-4 lg:px-6">
        <h2 class="h-display text-2xl sm:text-3xl text-center">Common questions</h2>
        <div class="mt-6 space-y-3" itemscope itemtype="https://schema.org/FAQPage">
            <?php foreach ([
                ['Are the classes really free for the first trial?', 'Yes. Every tuition supports a free trial class — no card needed. After the trial you can enrol monthly or walk away.'],
                ['Which devices work for live classes?', 'Any phone, tablet, or laptop with Chrome + a stable internet connection. We use Zoom for live classes.'],
                ['Can I refund a course I bought?', 'Yes — 7-day no-questions refund on all self-paced courses. Recurring tuitions can be cancelled anytime; you keep access till the end of the current cycle.'],
                ['Are instructors background-checked?', 'Yes. Every Khoobie-vetted instructor goes through ID verification, credential checks, and a teaching demo before they go live.'],
            ] as $f): ?>
                <div class="bg-slate-50 rounded-xl p-4" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="font-bold text-slate-900" itemprop="name"><?= esc($f[0]) ?></h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p class="mt-1 text-sm text-slate-700" itemprop="text"><?= esc($f[1]) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
