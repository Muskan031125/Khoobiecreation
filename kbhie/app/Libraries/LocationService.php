<?php

namespace App\Libraries;

use Config\Database;
use Config\Services;

/**
 * Sticky user location — city + locality + pincode.
 * Persisted to session + 90-day cookie so the user only picks once.
 *
 * Used by:
 *   - Home page: "Near you in {city}" hero section
 *   - Meetup listings: auto-filter by selected city
 *   - Concierge: passes city to LLM for hyperlocal recs
 *   - Catalog filters: pre-selects city
 *   - PDP: shows distance / "available in your city" badge
 */
class LocationService
{
    private const SESSION_KEY = 'location';
    private const COOKIE_KEY  = 'kb_loc';
    private const TTL_SECONDS = 60 * 60 * 24 * 90; // 90 days

    /**
     * Get the current location, falling back to:
     *  1. Session (fastest)
     *  2. Cookie (survives session expiry)
     *  3. Inferred from any meetup or partner the user previously interacted with
     *  4. Null (no location set)
     */
    public static function current(): ?array
    {
        $cached = session(self::SESSION_KEY);
        if (is_array($cached) && ! empty($cached['city'])) return $cached;

        $req = Services::request();
        $cookie = $req->getCookie(self::COOKIE_KEY);
        if ($cookie) {
            $parsed = json_decode((string) $cookie, true);
            if (is_array($parsed) && ! empty($parsed['city'])) {
                session()->set(self::SESSION_KEY, $parsed);
                return $parsed;
            }
        }
        return null;
    }

    /** Set the location — accepts city, optional locality + pincode. Returns the persisted record. */
    public static function set(string $city, ?string $locality = null, ?string $pincode = null): array
    {
        $rec = [
            'city'     => trim($city),
            'locality' => $locality ? trim($locality) : null,
            'pincode'  => $pincode  ? trim($pincode)  : null,
            'set_at'   => date('c'),
        ];
        session()->set(self::SESSION_KEY, $rec);
        Services::response()->setCookie(self::COOKIE_KEY, json_encode($rec), self::TTL_SECONDS);
        return $rec;
    }

    public static function clear(): void
    {
        session()->remove(self::SESSION_KEY);
        Services::response()->deleteCookie(self::COOKIE_KEY);
    }

    /** Returns the list of cities + locality counts available in the meetups table. Cached 10min. */
    public static function availableCities(): array
    {
        return \App\Libraries\CacheService::remember('loc:cities', function () {
            return Database::connect()->table('meetups')
                ->select('city, COUNT(*) AS n, COUNT(DISTINCT locality) AS localities')
                ->where('city IS NOT NULL', null, false)
                ->groupBy('city')
                ->orderBy('n', 'DESC')
                ->get()->getResultArray();
        }, 600);
    }

    /** Returns localities within the chosen city. */
    public static function localitiesIn(string $city): array
    {
        return Database::connect()->table('meetups')
            ->select('locality, COUNT(*) AS n')
            ->where('city', $city)
            ->where('locality IS NOT NULL', null, false)
            ->groupBy('locality')
            ->orderBy('n', 'DESC')
            ->get()->getResultArray();
    }

    /** Compact label like "Mumbai" or "Rohini, Delhi". */
    public static function label(): string
    {
        $l = self::current();
        if (! $l) return '';
        return ($l['locality'] ? $l['locality'] . ', ' : '') . $l['city'];
    }
}
