<?php

/**
 * Krafty Khoobie / Khoobie Creations — domain helpers.
 * Loaded globally via Config/Autoload.php.
 *
 * All money is stored in paise (INT). All datetimes in Asia/Kolkata.
 */

if (! function_exists('kb_money')) {
    /**
     * Format paise into Indian rupee with lakh/crore comma grouping.
     *  kb_money(1234567)   => "₹12,345.67"
     *  kb_money(10000000)  => "₹1,00,000.00"
     *  kb_money(100000000) => "₹10,00,000.00"
     */
    function kb_money(int $paise, bool $withSymbol = true, bool $withPaise = true): string
    {
        $rupees = $paise / 100;
        $whole  = (int) floor($rupees);
        $cents  = (int) round(($rupees - $whole) * 100);

        // Indian numbering system: last three digits, then 2-digit groups
        $str = (string) abs($whole);
        if (strlen($str) > 3) {
            $last3   = substr($str, -3);
            $rest    = substr($str, 0, -3);
            $rest    = preg_replace('/(\d)(?=(\d{2})+$)/', '$1,', $rest);
            $str     = $rest . ',' . $last3;
        }
        if ($whole < 0) $str = '-' . $str;

        $out = $withSymbol ? '₹' . $str : $str;
        if ($withPaise) $out .= '.' . str_pad((string) $cents, 2, '0', STR_PAD_LEFT);
        return $out;
    }
}

if (! function_exists('kb_money_short')) {
    /** kb_money_short(89900) => "₹899" (no paise, rounded). For card displays. */
    function kb_money_short(int $paise): string
    {
        return kb_money((int) round($paise / 100) * 100, true, false);
    }
}

if (! function_exists('kb_date')) {
    /**
     * Format a datetime in Indian conventions.
     *  kb_date('2026-05-24 15:30:00')        => "24 May 2026"
     *  kb_date('2026-05-24 15:30:00', true)  => "24 May 2026, 3:30 PM"
     *  kb_date('2026-05-24 15:30:00', true, 'short') => "24 May, 3:30 PM"
     */
    function kb_date(?string $datetime, bool $withTime = false, string $style = 'medium'): string
    {
        if (! $datetime) return '';
        $ts = is_numeric($datetime) ? (int) $datetime : strtotime($datetime);
        if (! $ts) return '';

        $datePart = match ($style) {
            'short'   => date('j M', $ts),                  // "24 May"
            'long'    => date('jS F Y', $ts),               // "24th May 2026"
            'numeric' => date('d/m/Y', $ts),                // "24/05/2026"
            default   => date('j M Y', $ts),                // "24 May 2026"
        };
        if (! $withTime) return $datePart;
        return $datePart . ', ' . date('g:i A', $ts);       // "24 May 2026, 3:30 PM"
    }
}

if (! function_exists('kb_relative')) {
    /** "3 hours ago", "yesterday", "in 2 days". */
    function kb_relative(?string $datetime): string
    {
        if (! $datetime) return '';
        $ts   = is_numeric($datetime) ? (int) $datetime : strtotime($datetime);
        $diff = time() - $ts;
        $abs  = abs($diff);
        $past = $diff >= 0;

        $units = [
            'year'   => 31536000,
            'month'  => 2592000,
            'week'   => 604800,
            'day'    => 86400,
            'hour'   => 3600,
            'minute' => 60,
        ];
        foreach ($units as $name => $secs) {
            if ($abs >= $secs) {
                $n = (int) floor($abs / $secs);
                $label = $n === 1 ? $name : $name . 's';
                return $past ? "$n $label ago" : "in $n $label";
            }
        }
        return 'just now';
    }
}

if (! function_exists('kb_phone')) {
    /** "+91 88992 23300" → "+91 88992 23300" (formats raw digits). */
    function kb_phone(?string $raw): string
    {
        if (! $raw) return '';
        $d = preg_replace('/\D/', '', $raw);
        if (strlen($d) === 10) return '+91 ' . substr($d, 0, 5) . ' ' . substr($d, 5);
        if (strlen($d) === 12 && str_starts_with($d, '91')) {
            return '+91 ' . substr($d, 2, 5) . ' ' . substr($d, 7);
        }
        return $raw;
    }
}

if (! function_exists('kb_pincode_zone')) {
    /** Rough delivery zone by Indian pincode prefix. Used for delivery estimates. */
    function kb_pincode_zone(string $pincode): array
    {
        $p = (int) substr($pincode, 0, 1);
        return match (true) {
            $p === 1 => ['zone' => 'North',         'days' => '2-4'],
            $p === 2 => ['zone' => 'North',         'days' => '2-4'],
            $p === 3 => ['zone' => 'West (Gujarat)','days' => '3-5'],
            $p === 4 => ['zone' => 'West',          'days' => '3-5'],
            $p === 5 => ['zone' => 'South Central', 'days' => '3-6'],
            $p === 6 => ['zone' => 'South',         'days' => '4-6'],
            $p === 7 => ['zone' => 'East',          'days' => '4-7'],
            $p === 8 => ['zone' => 'East / NE',     'days' => '5-8'],
            $p === 9 => ['zone' => 'NE / Forces',   'days' => '6-10'],
            default  => ['zone' => 'India',         'days' => '3-6'],
        };
    }
}

if (! function_exists('kb_can')) {
    /**
     * RBAC permission check.
     * Examples:
     *   kb_can('orders.confirm')     → true if user has admin/super_admin/staff with that perm
     *   kb_can('catalog.*')          → true if user has any catalog.* perm
     *   kb_can(['catalog.*','admin'])→ any of these
     */
    function kb_can(string|array $perm): bool
    {
        $user = session('user');
        if (! $user) return false;
        $roles = $user['roles'] ?? [];
        if (in_array('super_admin', $roles, true)) return true;

        $perms = (array) ($user['permissions'] ?? []);
        $needs = (array) $perm;

        foreach ($needs as $n) {
            if (in_array('*', $perms, true)) return true;
            if (in_array($n, $perms, true))  return true;
            // wildcard match: 'catalog.*' grants 'catalog.products.read' etc
            foreach ($perms as $have) {
                if (str_ends_with($have, '*') && str_starts_with($n, substr($have, 0, -1))) {
                    return true;
                }
                if (str_ends_with($n, '*') && str_starts_with($have, substr($n, 0, -1))) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (! function_exists('kb_safe_html')) {
    /**
     * Render a "trusted but sandboxed" HTML string for product descriptions, blog
     * bodies, and other admin-authored long-form content.
     *
     *  - Plain-text input  → escaped + line-broken (legacy behaviour preserved)
     *  - HTML input        → strips <script>/<style>/event handlers, allows a
     *                        rich-but-safe whitelist of formatting/structural tags.
     *
     * Why this exists: views used to call `nl2br(esc($product["long_desc"]))`,
     * which double-escaped HTML so `<p>` rendered as literal `&lt;p&gt;` text.
     */
    function kb_safe_html(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') return '';

        // Plain-text fast-path — no tags at all → behave like the old code.
        if (strpos($html, '<') === false) {
            return nl2br(esc($html));
        }

        // Kill script/style/iframe/object/embed blocks entirely (closing tag via backreference \1)
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
        // Kill inline event handlers (onclick=, onload=, etc.) — both quote styles
        $html = preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i', '', $html);
        $html = preg_replace("#\\son[a-z]+\\s*=\\s*'[^']*'#i", '', $html);
        // Kill javascript: URLs
        $html = preg_replace('#\s(href|src)\s*=\s*"javascript:[^"]*"#i', '', $html);
        $html = preg_replace("#\\s(href|src)\\s*=\\s*'javascript:[^']*'#i", '', $html);

        // Whitelist of allowed tags for product/blog body content
        $allowed = '<p><br><strong><b><em><i><u><s><mark><small>'
                 . '<ul><ol><li><dl><dt><dd>'
                 . '<h2><h3><h4><h5><h6>'
                 . '<a><blockquote><code><pre><hr>'
                 . '<table><thead><tbody><tr><th><td>'
                 . '<img><figure><figcaption><span><div>';
        return strip_tags($html, $allowed);
    }
}
