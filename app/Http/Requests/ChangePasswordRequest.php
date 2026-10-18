<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi ganti password sendiri (USR-3).
 */
class ChangePasswordRequest extends FormRequest
{
    /**
     * Setiap staf yang login boleh mengganti password sendiri.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Password lama wajib benar; password baru minimal 8 karakter dan dikonfirmasi.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    /**
     * Pesan error Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Password lama wajib diisi.',
            'current_password.current_password' => 'Password lama salah.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.*' => 'Password baru minimal 8 karakter.',
        ];
    }
}
