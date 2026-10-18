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
     * Kebijakan CSP dasar: semua sumber hanya dari domain sendiri.
     *
     * img-src mengizinkan data: karena CSS Bootstrap memakai ikon SVG
     * data URI untuk select, checkbox, dan validasi form.
     */
    private const CONTENT_SECURITY_POLICY = "default-src 'self'; img-src 'self' data:; object-src 'none'; "
        ."base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    /**
     * Menambahkan header keamanan ke respons.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', self::CONTENT_SECURITY_POLICY);
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
