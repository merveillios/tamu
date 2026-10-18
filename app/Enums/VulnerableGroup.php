<?php

namespace App\Enums;

/**
 * Pilihan kelompok rentan pada formulir tamu (PRD Bab 11.1, [DEFAULT] Bab 17 No. 23).
 */
enum VulnerableGroup: string
{
    case None = 'bukan';
    case Elderly = 'lansia';
    case Disability = 'disabilitas';
    case Pregnant = 'ibu_hamil';
    case Undisclosed = 'tidak_menyebutkan';

    /**
     * Label pilihan untuk tampilan UI dan ekspor.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'Bukan kelompok rentan',
            self::Elderly => 'Lansia',
            self::Disability => 'Penyandang disabilitas',
            self::Pregnant => 'Ibu hamil',
            self::Undisclosed => 'Tidak ingin menyebutkan',
        };
    }
}
