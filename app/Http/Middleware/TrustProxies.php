<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Proxy tepercaya agar IP klien dan skema HTTPS terbaca benar di balik reverse proxy (PRD Bab 5.5).
 * Daftar diambil dari TRUSTED_PROXIES; kosong berarti tidak ada proxy yang dipercaya.
 */
class TrustProxies extends Middleware
{
    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Proxy tepercaya dari config: '*' untuk semua, atau daftar IP/CIDR dipisah koma.
     *
     * @return array<int, string>|string|null
     */
    protected function proxies()
    {
        $configured = trim((string) config('sitamu.trusted_proxies'));

        if ($configured === '') {
            return null;
        }

        return $configured === '*' ? '*' : array_map('trim', explode(',', $configured));
    }
}
