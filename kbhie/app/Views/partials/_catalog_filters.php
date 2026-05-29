<?php
/**
 * Adaptive catalog filter sidebar — renders different controls per product family.
 *
 * Required scope:
 *   $filter_family   one of: meetups, classes, physical, digital, all
 *   $facets          pre-computed options (cities, localities, levels, price buckets)
 *   $filters         current selected values
 *   $category        current category row (or null)
 */
$family   = $filter_family ?? 'all';
$f        = $filters ?? [];
$facets   = $facets ?? [];

// Build current query string preserving non-filter params (sort, page, q)
$baseParams = array_filter([
    'q'     => $f['search'] ?? null,
    'sort'  => $f['sort']   ?? null,
]);

// Helper: emit a hidden input for every param NOT being changed by this filter group.
$hidden = function (array $skip) use ($f, $baseParams) {
    $out = '';
    foreach ($baseParams as $k => $v) {
        if (in_array($k, $skip, true)) continue;
        $out .= '<input type="hidden" name="' . esc($k, 'attr') . '" value="' . esc($v, 'attr') . '">';
    }
    foreach (['age_min','age_max','price_min','price_max','level','modality','city','locality','rating_min','in_stock'] as $k) {
        if (in_array($k, $skip, true) || empty($f[$k])) continue;
        $out .= '<input type="hidden" name="' . esc($k, 'attr') . '" value="' . esc((string) $f[$k], 'attr') . '">';
    }
    return $out;
};
?>
<aside class="lg:col-span-3 space-y-4" x-data="{ open: false }">

    <!-- Mobile filter toggle -->
    <button @click="open = !open"
            class="lg:hidden w-full px-4 py-2.5 rounded-lg bg-white border-2 border-slate-200 text-sm font-bold text-slate-900 flex items-center justify-between">
        <span class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M6 12h12M10 18h4"/></svg>
            Filters
        </span>
        <span class="text-xs text-slate-500" x-text="open ? 'Hide' : 'Show'"></span>
    </button>

    <div class="space-y-4" :class="{ 'hidden lg:block': ! open }">

        <!-- Active filters chips (one-click remove) -->
        <?php
        $activeChips = [];
        foreach (['age_min','age_max','price_min','price_max','level','modality','city','locality','rating_min','in_stock'] as $k) {
            if (! empty($f[$k])) {
                $label = $k . ': ' . $f[$k];
                if ($k === 'in_stock') $label = 'In stock';
                if ($k === 'rating_min') $label = '★ ' . $f[$k] . '+';
                if ($k === 'price_min') $label = 'Min ₹' . number_format(round($f[$k]/100));
                if ($k === 'price_max') $label = 'Max ₹' . number_format(round($f[$k]/100));
                if ($k === 'level' || $k === 'modality' || $k === 'city' || $k === 'locality') $label = ucfirst($f[$k]);
                $without = $f; unset($without[$k]);
                $href = '?' . http_build_query(array_filter(array_merge($baseParams, array_intersect_key($without, array_flip(['age_min','age_max','price_min','price_max','level','modality','city','locality','rating_min','in_stock']))), fn ($v) => $v !== null && $v !== ''));
                $activeChips[] = ['label' => $label, 'href' => $href];
            }
        }
        if (! empty($activeChips)): ?>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach ($activeChips as $chip): ?>
                    <a href="<?= esc($chip['href']) ?>" class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-brand-100 hover:bg-brand-200 text-brand-700 text-xs font-bold">
                        <?= esc($chip['label']) ?> ×
                    </a>
                <?php endforeach; ?>
                <a href="?<?= http_build_query($baseParams) ?>" class="inline-flex items-center px-2 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">Clear all</a>
            </div>
        <?php endif; ?>

        <!-- ============ MEETUPS family: city + locality ============ -->
        <?php if ($family === 'meetups'): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="eyebrow text-slate-500 mb-2">📍 Where</div>
                <form method="get" class="space-y-2">
                    <?= $hidden(['city', 'locality']) ?>
                    <select name="city" onchange="this.form.submit()"
                            class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-sm focus:border-brand-400 focus:outline-none">
                        <option value="">All cities</option>
                        <?php foreach (($facets['cities'] ?? []) as $c): ?>
                            <option value="<?= esc($c['city'], 'attr') ?>" <?= ($f['city'] ?? '') === $c['city'] ? 'selected' : '' ?>>
                                <?= esc($c['city']) ?> (<?= (int) $c['n'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if (! empty($f['city'])): ?>
                    <form method="get" class="mt-2">
                        <?= $hidden(['locality']) ?>
                        <select name="locality" onchange="this.form.submit()"
                                class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 text-sm focus:border-brand-400 focus:outline-none">
                            <option value="">All localities in <?= esc($f['city']) ?></option>
                            <?php foreach (($facets['localities'] ?? []) as $loc): ?>
                                <?php if ($loc['city'] !== $f['city']) continue; ?>
                                <option value="<?= esc($loc['locality'], 'attr') ?>" <?= ($f['locality'] ?? '') === $loc['locality'] ? 'selected' : '' ?>>
                                    <?= esc($loc['locality']) ?> (<?= (int) $loc['n'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ============ CLASSES family: level + modality ============ -->
        <?php if ($family === 'classes'): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="eyebrow text-slate-500 mb-2">🎓 Level</div>
                <div class="space-y-1">
                    <?php foreach (['beginner','intermediate','advanced'] as $lvl):
                        $params = array_filter(array_merge($baseParams, array_intersect_key($f, array_flip(['age_min','age_max','price_min','price_max','modality','city','locality','rating_min','in_stock']))), fn ($v) => $v !== null && $v !== '');
                        $params['level'] = ($f['level'] ?? '') === $lvl ? '' : $lvl;
                    ?>
                        <a href="?<?= http_build_query(array_filter($params, fn ($v) => $v !== '')) ?>"
                           class="flex items-center justify-between px-2.5 py-1.5 rounded-md text-sm transition <?= ($f['level'] ?? '') === $lvl ? 'bg-brand-100 text-brand-700 font-bold' : 'hover:bg-slate-50 text-slate-700' ?>">
                            <span><?= ucfirst($lvl) ?></span>
                            <?php if (($f['level'] ?? '') === $lvl): ?><span>✓</span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="eyebrow text-slate-500 mb-2">💻 Mode</div>
                <div class="space-y-1">
                    <?php foreach ([['online','Online live'],['offline','In-person'],['hybrid','Hybrid']] as [$val, $label]):
                        $params = array_filter(array_merge($baseParams, array_intersect_key($f, array_flip(['age_min','age_max','price_min','price_max','level','city','locality','rating_min','in_stock']))), fn ($v) => $v !== null && $v !== '');
                        $params['modality'] = ($f['modality'] ?? '') === $val ? '' : $val;
                    ?>
                        <a href="?<?= http_build_query(array_filter($params, fn ($v) => $v !== '')) ?>"
                           class="flex items-center justify-between px-2.5 py-1.5 rounded-md text-sm transition <?= ($f['modality'] ?? '') === $val ? 'bg-brand-100 text-brand-700 font-bold' : 'hover:bg-slate-50 text-slate-700' ?>">
                            <span><?= esc($label) ?></span>
                            <?php if (($f['modality'] ?? '') === $val): ?><span>✓</span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============ PHYSICAL family: price + age + rating + stock ============ -->
        <?php if (in_array($family, ['physical','digital','all'], true) && ! empty($facets['priceBuckets'])): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="eyebrow text-slate-500 mb-2">₹ Price</div>
                <div class="space-y-1">
                    <?php foreach ($facets['priceBuckets'] as $b):
                        $isActive = ((int)($f['price_min'] ?? 0) === ($b['min'] ?? 0)) && ((int)($f['price_max'] ?? 0) === ($b['max'] ?? 0));
                        $params = array_filter(array_merge($baseParams, array_intersect_key($f, array_flip(['age_min','age_max','level','modality','city','locality','rating_min','in_stock']))), fn ($v) => $v !== null && $v !== '');
                        if (! $isActive) {
                            if (! empty($b['min'])) $params['price_min'] = $b['min'];
                            if (! empty($b['max'])) $params['price_max'] = $b['max'];
                        }
                    ?>
                        <a href="?<?= http_build_query(array_filter($params, fn ($v) => $v !== '')) ?>"
                           class="flex items-center justify-between px-2.5 py-1.5 rounded-md text-sm transition <?= $isActive ? 'bg-brand-100 text-brand-700 font-bold' : 'hover:bg-slate-50 text-slate-700' ?>">
                            <span><?= esc($b['label']) ?></span>
                            <?php if ($isActive): ?><span>✓</span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============ Universal: age band + rating + stock ============ -->
        <?php if (in_array($family, ['physical','digital','classes','all'], true)): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="eyebrow text-slate-500 mb-2">👶 Age band</div>
                <div class="space-y-1">
                    <?php foreach ([[2,4,'2–4 yrs'],[4,7,'4–7 yrs'],[7,10,'7–10 yrs'],[10,13,'10–13 yrs'],[13,17,'13–17 yrs']] as [$min, $max, $label]):
                        $isActive = (int) ($f['age_min'] ?? 0) === $min && (int) ($f['age_max'] ?? 0) === $max;
                        $params = array_filter(array_merge($baseParams, array_intersect_key($f, array_flip(['price_min','price_max','level','modality','city','locality','rating_min','in_stock']))), fn ($v) => $v !== null && $v !== '');
                        if (! $isActive) { $params['age_min'] = $min; $params['age_max'] = $max; }
                    ?>
                        <a href="?<?= http_build_query(array_filter($params, fn ($v) => $v !== '')) ?>"
                           class="flex items-center justify-between px-2.5 py-1.5 rounded-md text-sm transition <?= $isActive ? 'bg-brand-100 text-brand-700 font-bold' : 'hover:bg-slate-50 text-slate-700' ?>">
                            <span><?= esc($label) ?></span>
                            <?php if ($isActive): ?><span>✓</span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Universal: rating + stock -->
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="eyebrow text-slate-500 mb-2">★ Rating</div>
            <div class="space-y-1">
                <?php foreach (['4.5'=>'★ 4.5 & up','4.0'=>'★ 4.0 & up','3.5'=>'★ 3.5 & up'] as $val => $label):
                    $isActive = (string) ($f['rating_min'] ?? '') === $val;
                    $params = array_filter(array_merge($baseParams, array_intersect_key($f, array_flip(['age_min','age_max','price_min','price_max','level','modality','city','locality','in_stock']))), fn ($v) => $v !== null && $v !== '');
                    if (! $isActive) $params['rating_min'] = $val;
                ?>
                    <a href="?<?= http_build_query(array_filter($params, fn ($v) => $v !== '')) ?>"
                       class="flex items-center justify-between px-2.5 py-1.5 rounded-md text-sm transition <?= $isActive ? 'bg-brand-100 text-brand-700 font-bold' : 'hover:bg-slate-50 text-slate-700' ?>">
                        <span class="text-amber-500 font-bold"><?= esc($label) ?></span>
                        <?php if ($isActive): ?><span class="text-brand-700">✓</span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($family === 'physical' || $family === 'all'): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <?php $isActive = ! empty($f['in_stock']);
                    $params = array_filter(array_merge($baseParams, array_intersect_key($f, array_flip(['age_min','age_max','price_min','price_max','level','modality','city','locality','rating_min']))), fn ($v) => $v !== null && $v !== '');
                    if (! $isActive) $params['in_stock'] = 1;
                ?>
                <a href="?<?= http_build_query(array_filter($params, fn ($v) => $v !== '')) ?>"
                   class="flex items-center justify-between px-2.5 py-1.5 rounded-md text-sm transition <?= $isActive ? 'bg-emerald-100 text-emerald-700 font-bold' : 'hover:bg-slate-50 text-slate-700' ?>">
                    <span>📦 Only show in-stock</span>
                    <?php if ($isActive): ?><span>✓</span><?php endif; ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</aside>
