<?php

namespace App\Enums;

/**
 * Status QR Code. QR berstatus revoked tidak dapat diaktifkan kembali (QR-3).
 */
enum QrStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Revoked = 'revoked';

    /**
     * Label status untuk tampilan UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
            self::Revoked => 'Dicabut',
        };
    }

    /**
     * Warna badge Bootstrap untuk status ini.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'secondary',
            self::Revoked => 'danger',
        };
    }
}
