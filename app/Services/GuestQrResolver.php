<?php

namespace App\Services;

use App\Enums\QrStatus;
use App\Models\QrCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Mencari QR dari token di URL halaman tamu (TAM-1, TAM-2, TAM-6).
 *
 * Lokasi dan QR kunjungan selalu berasal dari token ini, tidak pernah dari input tamu.
 */
class GuestQrResolver
{
    /**
     * Pesan seragam untuk semua QR yang tidak dapat dipakai (TAM-6).
     */
    public const INACTIVE_MESSAGE = 'QR Code ini tidak aktif. Silakan hubungi petugas di lokasi.';

    /**
     * QR aktif di lokasi aktif yang memiliki minimal satu kategori aktif; selain itu 404.
     */
    public function resolve(string $token): QrCode
    {
        $qr = preg_match('/^[0-9a-f]{64}$/', $token) === 1
            ? QrCode::query()->where('token', $token)->with('location')->first()
            : null;

        if (! $this->isUsable($qr)) {
            throw new HttpException(404, self::INACTIVE_MESSAGE);
        }

        return $qr;
    }

    /**
     * Mengecek status QR, status lokasi, dan ketersediaan kategori aktif.
     */
    private function isUsable(?QrCode $qr): bool
    {
        return $qr !== null
            && $qr->status === QrStatus::Active
            && $qr->location !== null
            && $qr->location->is_active
            && $qr->location->purposeCategories()->where('is_active', true)->exists();
    }
}
