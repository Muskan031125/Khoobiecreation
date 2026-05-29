<?php

namespace App\Libraries;

use Config\Services;

/**
 * Thin wrapper around CodeIgniter's cache service for hot queries.
 * Each call ensures the cache is checked first; on miss, the closure runs + result stored.
 *
 * Usage:
 *   $cats = CacheService::remember('cat:all', fn() => Database::connect()->table('categories')->...->getResultArray(), 600);
 *
 * Bump cache version (PUBLIC_CACHE_VER env) to invalidate everything.
 */
class CacheService
{
    private const PREFIX = 'kb';

    public static function remember(string $key, callable $producer, int $ttlSeconds = 300)
    {
        $cache = Services::cache();
        $key   = self::PREFIX . ':v' . env('public_cache_ver', '1') . ':' . $key;
        $val   = $cache->get($key);
        if ($val !== null) return $val;
        $val = $producer();
        try { $cache->save($key, $val, $ttlSeconds); } catch (\Throwable $e) {}
        return $val;
    }

    public static function forget(string $key): void
    {
        $key = self::PREFIX . ':v' . env('public_cache_ver', '1') . ':' . $key;
        Services::cache()->delete($key);
    }
}
