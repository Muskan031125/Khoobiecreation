<?php

namespace App\Modules\Storefront\Controllers;

use Config\Database;
use Config\Services;

/**
 * Dynamic Open Graph share images.
 *   /og/product/{slug}.png  — 1200×630 PNG per product (cached on disk)
 *
 * Renders: hero on left half, title + price + brand overlay on right half.
 * Cached to public/og/{slug}.png on first generation, served as a static
 * file on subsequent hits by .htaccess fallthrough.
 */
class OgImageController extends BaseStoreController
{
    public function product(string $slug)
    {
        $slug = preg_replace('/\.png$/', '', $slug);

        $db = Database::connect();
        $p  = $db->table('products p')
            ->select('p.slug, p.name, p.hero_image, v.price, v.compare_at_price')
            ->join('product_variants v', 'v.product_id = p.id AND v.is_default = 1', 'left')
            ->where('p.slug', $slug)->where('p.status', 'active')
            ->get()->getRowArray();
        if (! $p) return $this->response->setStatusCode(404)->setBody('');

        $cacheDir = FCPATH . 'og';
        if (! is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
        $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $slug . '.png';

        // Cache 1 day — regenerate if file > 24h old
        if (file_exists($cachePath) && filemtime($cachePath) > time() - 86400) {
            return $this->response
                ->setHeader('Content-Type', 'image/png')
                ->setHeader('Cache-Control', 'public, max-age=86400')
                ->setBody(file_get_contents($cachePath));
        }

        $png = $this->renderProductOg($p);
        if (! $png) return $this->response->setStatusCode(500)->setBody('GD not available');

        file_put_contents($cachePath, $png);
        return $this->response
            ->setHeader('Content-Type', 'image/png')
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody($png);
    }

    /** Compose a 1200×630 brand-aligned share card via GD. */
    private function renderProductOg(array $p): ?string
    {
        if (! function_exists('imagecreatetruecolor')) return null;

        $W = 1200; $H = 630;
        $im = imagecreatetruecolor($W, $H);

        // Brand-orange to amber gradient background
        for ($y = 0; $y < $H; $y++) {
            $t = $y / $H;
            $r = (int) (255 - $t * 35);
            $g = (int) (111 + $t * 100);
            $b = (int) (97  + $t * 30);
            $color = imagecolorallocate($im, $r, $g, $b);
            imageline($im, 0, $y, $W, $y, $color);
        }

        // White card with rounded inset
        $card = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 40, 40, $W - 40, $H - 40, $card);

        // ----- Left half: hero image
        $heroUrl = $p['hero_image'];
        if ($heroUrl) {
            try {
                $heroData = @file_get_contents($heroUrl);
                if ($heroData) {
                    $src = @imagecreatefromstring($heroData);
                    if ($src) {
                        $sw = imagesx($src); $sh = imagesy($src);
                        $dst = imagecreatetruecolor(540, 530);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, 540, 530, $sw, $sh);
                        imagecopy($im, $dst, 60, 50, 0, 0, 540, 530);
                        imagedestroy($src);
                        imagedestroy($dst);
                    }
                }
            } catch (\Throwable $e) { /* ignore image fetch errors */ }
        }

        // ----- Right half: title + price + brand badge
        $textColor   = imagecolorallocate($im, 15, 23, 42);    // slate-900
        $accentColor = imagecolorallocate($im, 255, 111, 97);  // brand-500
        $greyColor   = imagecolorallocate($im, 100, 116, 139); // slate-500

        // Try to use a TTF font; fall back to GD built-in if unavailable
        $fontTtf = FCPATH . 'assets/og-font.ttf';
        $useTtf  = file_exists($fontTtf);

        if ($useTtf) {
            imagettftext($im, 18, 0, 640, 110, $accentColor, $fontTtf, strtoupper('Krafty Khoobie'));
            $title = $this->wrap($p['name'], 22);
            $y = 180;
            foreach ($title as $line) {
                imagettftext($im, 38, 0, 640, $y, $textColor, $fontTtf, $line);
                $y += 56;
            }
            $price = $p['price'] ? '₹' . number_format(round($p['price'] / 100)) : '';
            $mrp   = ($p['compare_at_price'] && $p['compare_at_price'] > $p['price']) ? '₹' . number_format(round($p['compare_at_price'] / 100)) : '';
            imagettftext($im, 48, 0, 640, 480, $accentColor, $fontTtf, $price);
            if ($mrp) imagettftext($im, 24, 0, 640 + (strlen($price) * 28), 478, $greyColor, $fontTtf, $mrp);
            imagettftext($im, 18, 0, 640, 540, $greyColor, $fontTtf, 'Hands-on. Heart-led. Screen-free.');
        } else {
            // Built-in font fallback (low-quality but reliable)
            imagestring($im, 5, 640, 90,  'KRAFTY KHOOBIE', $accentColor);
            $title = $this->wrap($p['name'], 28);
            $y = 150;
            foreach ($title as $line) {
                imagestring($im, 5, 640, $y, $line, $textColor);
                $y += 30;
            }
            $price = $p['price'] ? 'Rs. ' . number_format(round($p['price'] / 100)) : '';
            imagestring($im, 5, 640, 460, $price, $accentColor);
            imagestring($im, 4, 640, 510, 'Hands-on. Heart-led. Screen-free.', $greyColor);
        }

        ob_start();
        imagepng($im, null, 9);
        $bin = ob_get_clean();
        imagedestroy($im);
        return $bin;
    }

    private function wrap(string $text, int $width): array
    {
        $words = explode(' ', $text);
        $lines = []; $cur = '';
        foreach ($words as $w) {
            if (strlen($cur . ' ' . $w) > $width) {
                if ($cur !== '') $lines[] = $cur;
                $cur = $w;
            } else $cur = trim($cur . ' ' . $w);
        }
        if ($cur !== '') $lines[] = $cur;
        return array_slice($lines, 0, 4);
    }
}
