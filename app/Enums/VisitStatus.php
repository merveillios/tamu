<?php

namespace App\Enums;

/**
 * Status kunjungan yang tersimpan di database (PRD Bab 0.4).
 *
 * Label "Menunggu PIC" bukan status tersendiri; nilainya dihitung oleh
 * accessor displayStatus pada model GuestVisit.
 */
enum VisitStatus: string
{
    case Waiting = 'waiting';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Label status untuk tampilan UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu',
            self::Processing => 'Sedang direview',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Warna badge Bootstrap untuk status ini.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Waiting => 'warning',
            self::Processing => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Menentukan apakah kunjungan masih termasuk Tamu Aktif.
     */
    public function isActive(): bool
    {
        return $this === self::Waiting || $this === self::Processing;
    }
}
