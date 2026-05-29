<?php
// Required: $currentPage, $totalPages, $totalRows, $perPage, $perPageOptions
// Optional: $extraQs (assoc array of preserved query params)
$extraQs = $extraQs ?? [];
$qs = function (array $overrides) use ($extraQs) {
    $merged = array_filter(array_merge($extraQs, $overrides), static fn ($v) => $v !== null && $v !== '');
    return $merged ? '?' . http_build_query($merged) : '';
};
$start = $totalRows === 0 ? 0 : (($currentPage - 1) * $perPage + 1);
$end   = min($totalRows, $currentPage * $perPage);
$path  = trim(parse_url(current_url(), PHP_URL_PATH), '/');
$base  = '/' . $path;

// Render up to 7 page links centered on current
$range = [];
if ($totalPages <= 7) {
    for ($i = 1; $i <= $totalPages; $i++) $range[] = $i;
} else {
    $range[] = 1;
    if ($currentPage > 4) $range[] = '...';
    $from = max(2, $currentPage - 2);
    $to   = min($totalPages - 1, $currentPage + 2);
    for ($i = $from; $i <= $to; $i++) $range[] = $i;
    if ($currentPage < $totalPages - 3) $range[] = '...';
    $range[] = $totalPages;
}
?>
<div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
    <div class="text-slate-500">
        <?php if ($totalRows === 0): ?>
            No results.
        <?php else: ?>
            Showing <strong class="text-slate-900"><?= number_format($start) ?>–<?= number_format($end) ?></strong>
            of <strong class="text-slate-900"><?= number_format($totalRows) ?></strong>
        <?php endif; ?>
        <span class="ml-3">
            <label class="text-slate-500">Rows:</label>
            <select onchange="location.href='<?= esc($base, 'attr') ?><?= esc($qs([]), 'attr') ?>'.replace(/[?&]per_page=\d+/, '') + ('<?= esc($qs([]), 'js') ?>'.includes('?') ? '&' : '?') + 'per_page=' + this.value" class="ml-1 px-2 py-1 rounded border border-slate-200 text-xs bg-white">
                <?php foreach ($perPageOptions as $opt): ?>
                    <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </span>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="flex items-center gap-1">
            <a href="<?= esc($base . $qs(['page' => max(1, $currentPage - 1)])) ?>"
               class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 <?= $currentPage <= 1 ? 'pointer-events-none opacity-40' : '' ?>" aria-label="Previous">&lsaquo;</a>
            <?php foreach ($range as $r): if ($r === '...'): ?>
                <span class="px-2 text-slate-400">…</span>
            <?php else: ?>
                <a href="<?= esc($base . $qs(['page' => $r])) ?>"
                   class="px-3 py-1.5 rounded-md border <?= $r === $currentPage ? 'bg-slate-900 text-white border-slate-900 font-bold' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' ?>"><?= $r ?></a>
            <?php endif; endforeach; ?>
            <a href="<?= esc($base . $qs(['page' => min($totalPages, $currentPage + 1)])) ?>"
               class="px-2.5 py-1.5 rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>" aria-label="Next">&rsaquo;</a>
        </nav>
    <?php endif; ?>
</div>
