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
     * QR aktif di lokasi aktif yang memiliki minimal satu Bidang/UPT aktif; selain itu 404.
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
     * Alasan QR berstatus aktif belum dapat dipakai tamu; kosong berarti siap dipakai.
     *
     * Dipakai juga oleh daftar QR staf agar label "Aktif" di sana selalu sama dengan
     * perilaku halaman tamu. Jumlah Bidang/UPT dihitung pemanggil agar daftar tidak
     * menjalankan satu query per baris. Tanpa Bidang/UPT aktif, kunjungan tidak dapat
     * ditugaskan ke siapa pun, sehingga QR belum boleh dipakai tamu.
     *
     * @return array<int, string>
     */
    public function blockers(QrCode $qr, bool $hasActiveDepartment): array
    {
        if ($qr->location === null) {
            return ['Lokasi sudah dihapus.'];
        }

        return array_values(array_filter([
            $qr->location->is_active ? null : 'Lokasi nonaktif.',
            $hasActiveDepartment ? null : 'Belum ada Bidang/UPT aktif.',
        ]));
    }

    /**
     * Mengecek status QR, status lokasi, dan ketersediaan Bidang/UPT aktif.
     */
    private function isUsable(?QrCode $qr): bool
    {
        return $qr !== null
            && $qr->status === QrStatus::Active
            && $this->blockers($qr, $this->hasActiveDepartment($qr)) === [];
    }

    /**
     * Mengecek apakah lokasi QR memiliki minimal satu Bidang/UPT aktif.
     */
    private function hasActiveDepartment(QrCode $qr): bool
    {
        return $qr->location?->departments()->where('is_active', true)->exists() ?? false;
    }
}
