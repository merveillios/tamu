<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form login staf (AUTH-1).
 */
class LoginRequest extends FormRequest
{
    /**
     * Form login boleh dikirim siapa pun yang belum login.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi email dan password.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Pesan seragam agar tidak membocorkan field mana yang salah.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.*' => 'Email atau password salah.',
            'password.*' => 'Email atau password salah.',
        ];
    }
}
