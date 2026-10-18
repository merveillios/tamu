<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi alasan buka kembali kunjungan oleh Admin (ADM-3, Bab 11.2).
 */
class ReopenVisitRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Alasan wajib, maksimal 255 karakter.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['note' => ['required', 'string', 'max:255']];
    }

    /**
     * Pesan error Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['note.*' => 'Alasan buka kembali wajib diisi (maks. 255 karakter).'];
    }
}
