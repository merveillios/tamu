<?php

namespace App\Rules;

use App\Services\SignatureService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Aturan validasi tanda tangan PNG dari kotak tanda tangan (TAM-9, Bab 11.1).
 */
class ValidSignatureImage implements ValidationRule
{
    /**
     * Menolak data URL yang tidak memenuhi aturan SignatureService.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(SignatureService::class)->isValid(is_string($value) ? $value : null)) {
            $fail('Tanda tangan wajib dibubuhkan.');
        }
    }
}
