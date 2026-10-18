<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Memverifikasi ID token Google lewat endpoint tokeninfo (TAM-11, Bab 17 No. 14).
 *
 * Hanya email dan status verifikasinya yang dibaca; token tidak pernah ditulis ke log.
 */
class GoogleTokenVerifier
{
    private const TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * Mengembalikan email (huruf kecil) bila token sah, atau null bila tidak.
     */
    public function verify(string $idToken, string $expectedNonce): ?string
    {
        try {
            $response = Http::timeout(5)->get(self::TOKENINFO_URL, ['id_token' => $idToken]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $claims = $response->json();

        return $this->claimsAreValid($claims, $expectedNonce) ? Str::lower($claims['email']) : null;
    }

    /**
     * Memeriksa aud, iss, exp, email_verified, dan nonce.
     *
     * @param  mixed  $claims
     */
    private function claimsAreValid($claims, string $expectedNonce): bool
    {
        if (! is_array($claims) || empty($claims['email']) || empty($claims['nonce'])) {
            return false;
        }

        $clientId = (string) config('sitamu.google_client_id');
        // tokeninfo mengembalikan email_verified sebagai string "true".
        $emailVerified = in_array($claims['email_verified'] ?? null, [true, 'true'], true);

        return $clientId !== ''
            && hash_equals($clientId, (string) ($claims['aud'] ?? ''))
            && in_array($claims['iss'] ?? null, self::ISSUERS, true)
            && (int) ($claims['exp'] ?? 0) > now()->timestamp
            && $emailVerified
            && $expectedNonce !== ''
            && hash_equals($expectedNonce, (string) $claims['nonce']);
    }
}
