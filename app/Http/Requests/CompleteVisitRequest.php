<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi hasil saat PIC menyelesaikan kunjungan (PIC-3, Bab 11.2).
 */
class CompleteVisitRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Hasil wajib 5–2000 karakter setelah trim; hasil yang hanya berisi spasi ditolak.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['result' => ['required', 'string', 'min:5', 'max:2000']];
    }

    /**
     * Pesan error Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['result.*' => 'Hasil wajib diisi (5–2000 karakter).'];
    }
}
