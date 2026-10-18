<?php

namespace App\Enums;

/**
 * Peran akun staf (PRD Bab 3). Tamu tidak memiliki akun.
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Receptionist = 'receptionist';
    case Pic = 'pic';

    /**
     * Nama peran untuk tampilan UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Receptionist => 'Resepsionis',
            self::Pic => 'PIC',
        };
    }
}
