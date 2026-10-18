<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nomor telepon/WhatsApp Indonesia (Bab 11.1). Disimpan berawalan 62, ditampilkan berawalan 0.
 */
class IndonesianPhone implements ValidationRule
{
    /**
     * Menolak nomor yang setelah dinormalisasi bukan 10–15 digit berawalan 62.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize(is_string($value) ? $value : '') === null) {
            $fail('Nomor telepon tidak valid. Contoh: 081234567890.');
        }
    }

    /**
     * Membersihkan spasi, tanda hubung, kurung, dan plus, lalu mengganti awalan 0 menjadi 62.
     */
    public static function normalize(string $value): ?string
    {
        $digits = preg_replace('/[\s\-().+]/', '', $value);

        if ($digits === null || ! ctype_digit($digits)) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        $length = strlen($digits);

        return str_starts_with($digits, '62') && $length >= 10 && $length <= 15 ? $digits : null;
    }

    /**
     * Format tampilan berawalan 0, misalnya 6281234567890 menjadi 081234567890.
     */
    public static function display(string $stored): string
    {
        return str_starts_with($stored, '62') ? '0'.substr($stored, 2) : $stored;
    }
}
