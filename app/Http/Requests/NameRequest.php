<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi master data yang hanya berisi nama: departemen dan petugas (Bab 11.3).
 * Keunikan per lokasi atau per departemen dicek di service terkait.
 */
class NameRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Merapikan spasi berlebih pada nama.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name')))]);
    }

    /**
     * Nama wajib 2–100 karakter.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:100']];
    }

    /**
     * Pesan error Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.*' => 'Nama wajib diisi (2–100 karakter).'];
    }
}
