<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PwaIconController extends Controller
{
    /**
     * Sert une icône PWA générée (évite les 404 du manifest).
     * Taille déduite du nom (ex. icon-152x152.png → 152).
     */
    public function __invoke(string $filename): StreamedResponse|Response
    {
        if (! preg_match('/^icon-(\d+)x\d+\.png$/', $filename, $m)) {
            abort(404);
        }

        $size = (int) $m[1];
        if ($size < 1 || $size > 512) {
            abort(404);
        }

        if (! extension_loaded('gd')) {
            return $this->minimalPngResponse();
        }

        $img = @imagecreatetruecolor($size, $size);
        if ($img === false) {
            return $this->minimalPngResponse();
        }

        $amber = imagecolorallocate($img, 245, 158, 11);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $amber);

        $margin = (int) round($size * 0.2);
        $cx = (int) ($size / 2);
        $cy = (int) ($size / 2);
        $r = (int) round(($size - 2 * $margin) / 2 * 0.4);
        imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $white);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function minimalPngResponse(): Response
    {
        $minimalPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        return response($minimalPng, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
