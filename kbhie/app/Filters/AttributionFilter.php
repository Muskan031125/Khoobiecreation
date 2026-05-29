<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AttributionFilter — fires before every storefront request.
 *
 * Persists the first-touch acquisition source (utm_*, gclid, fbclid, ref/r code,
 * landing page, referrer) into the session AND a 90-day first-touch cookie so we
 * can attribute orders, leads, intents, and referrals to the channel that originally
 * brought the visitor in.
 *
 * Stores under session('attribution') and cookie kb_attr:
 *   {
 *     source, medium, campaign, term, content,
 *     gclid, fbclid, ref_code,
 *     landing_url, landing_referrer, first_seen_at
 *   }
 */
class AttributionFilter implements FilterInterface
{
    private const COOKIE_KEY = 'kb_attr';
    private const TTL_SEC    = 60 * 60 * 24 * 90;   // 90 days

    public function before(RequestInterface $request, $arguments = null)
    {
        $params = $request->getGet();

        $candidate = array_filter([
            'source'   => $params['utm_source']   ?? null,
            'medium'   => $params['utm_medium']   ?? null,
            'campaign' => $params['utm_campaign'] ?? null,
            'term'     => $params['utm_term']     ?? null,
            'content'  => $params['utm_content']  ?? null,
            'gclid'    => $params['gclid']        ?? null,
            'fbclid'   => $params['fbclid']       ?? null,
            'ref_code' => $params['ref']          ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $session = session();
        $existing = $session->get('attribution') ?: $this->readCookie($request);

        // Last-touch update for utm/gclid/fbclid hits — overwrite if new params arrived
        if (! empty($candidate)) {
            $merged = array_merge($existing ?: [], $candidate, [
                'landing_url'      => current_url(),
                'landing_referrer' => (string) $request->getServer('HTTP_REFERER'),
                'first_seen_at'    => $existing['first_seen_at'] ?? date('c'),
                'last_touch_at'    => date('c'),
            ]);
            $session->set('attribution', $merged);
            $this->writeCookie($merged);
        } elseif (! $existing) {
            // Brand new visitor with no UTM — still record landing for organic attribution
            $organic = [
                'source'           => $this->classifyReferrer((string) $request->getServer('HTTP_REFERER')),
                'medium'           => 'organic',
                'landing_url'      => current_url(),
                'landing_referrer' => (string) $request->getServer('HTTP_REFERER'),
                'first_seen_at'    => date('c'),
            ];
            $session->set('attribution', $organic);
            $this->writeCookie($organic);
        } else {
            // Restore from cookie if session was wiped
            $session->set('attribution', $existing);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}

    private function readCookie(RequestInterface $request): ?array
    {
        $raw = $request->getCookie(self::COOKIE_KEY);
        if (! $raw) return null;
        $parsed = json_decode((string) $raw, true);
        return is_array($parsed) ? $parsed : null;
    }

    private function writeCookie(array $payload): void
    {
        $resp = \Config\Services::response();
        $resp->setCookie(self::COOKIE_KEY, json_encode($payload), self::TTL_SEC);
    }

    /** "google.com" → "google"; "facebook.com" → "facebook"; empty → "direct". */
    private function classifyReferrer(string $url): string
    {
        if (! $url) return 'direct';
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        if (str_contains($host, 'google'))    return 'google';
        if (str_contains($host, 'bing'))      return 'bing';
        if (str_contains($host, 'duckduckgo'))return 'duckduckgo';
        if (str_contains($host, 'facebook'))  return 'facebook';
        if (str_contains($host, 'instagram')) return 'instagram';
        if (str_contains($host, 'twitter'))   return 'twitter';
        if (str_contains($host, 'x.com'))     return 'twitter';
        if (str_contains($host, 'youtube'))   return 'youtube';
        if (str_contains($host, 'whatsapp'))  return 'whatsapp';
        if (str_contains($host, 'reddit'))    return 'reddit';
        if (str_contains($host, 'chatgpt'))   return 'chatgpt';
        if (str_contains($host, 'perplexity'))return 'perplexity';
        if (str_contains($host, 'claude'))    return 'claude';
        if (str_contains($host, 'gemini'))    return 'gemini';
        return 'referral';
    }
}
