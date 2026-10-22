<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memasang header keamanan pada setiap respons web (PRD Bab 12.1).
 */
class SecurityHeaders
{
    /**
     * Atribut request yang menandai halaman Langkah 1 tamu (tombol Google Identity Services).
     */
    public const ALLOW_GOOGLE_SIGN_IN = 'csp.allow_google_sign_in';

    /**
     * Kebijakan CSP dasar: semua sumber hanya dari domain sendiri.
     *
     * img-src mengizinkan data: karena CSS Bootstrap memakai ikon SVG
     * data URI untuk select, checkbox, dan validasi form.
     */
    private const CONTENT_SECURITY_POLICY = "default-src 'self'; img-src 'self' data:; object-src 'none'; "
        ."base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    /**
     * img-src khusus halaman tamu: tambahan foto profil Google.
     */
    private const GOOGLE_IMG_SOURCES = "img-src 'self' data: https://*.googleusercontent.com https://*.gstatic.com";

    /**
     * Tambahan sumber Google khusus halaman Langkah 1 (Bab 12.1).
     *
     * Google Identity Services memuat script/iframe/koneksi dari beberapa origin
     * (accounts.google.com, apis.google.com, oauth2.googleapis.com, gstatic.com),
     * sehingga yang diizinkan adalah origin-nya, bukan satu path saja.
     */
    private const GOOGLE_SIGN_IN_SOURCES = "; script-src 'self' https://accounts.google.com https://apis.google.com"
        ."; frame-src https://accounts.google.com"
        ."; connect-src 'self' https://accounts.google.com https://oauth2.googleapis.com"
        ."; style-src 'self' 'unsafe-inline' https://accounts.google.com";

    /**
     * Menambahkan header keamanan ke respons.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $policy = self::CONTENT_SECURITY_POLICY;

        if ($request->attributes->get(self::ALLOW_GOOGLE_SIGN_IN) === true) {
            // Ganti img-src dasar dengan versi yang mengizinkan foto profil Google,
            // karena directive CSP yang sama hanya dihitung yang pertama.
            $policy = str_replace("img-src 'self' data:", self::GOOGLE_IMG_SOURCES, $policy);
            $policy .= self::GOOGLE_SIGN_IN_SOURCES;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', $policy);
        $response->headers->set('Referrer-Policy', $this->referrerPolicy($request));

        return $response;
    }

    /**
     * Halaman tamu tidak mengirim referrer sama sekali agar token QR tidak bocor.
     */
    private function referrerPolicy(Request $request): string
    {
        return $request->is('tamu/*') ? 'no-referrer' : 'strict-origin-when-cross-origin';
    }
}
