<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi buat QR dan ganti akun resepsionis QR (QR-2, QR-6, Bab 11.3).
 */
class QrRequest extends FormRequest
{
    /**
     * Hanya Super Admin yang mengelola QR.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::SuperAdmin) ?? false;
    }

    /**
     * Merapikan label; label kosong disimpan sebagai null.
     */
    protected function prepareForValidation(): void
    {
        $label = trim((string) $this->input('label'));

        $this->merge(['label' => $label === '' ? null : preg_replace('/\s+/', ' ', $label)]);
    }

    /**
     * Akun resepsionis wajib aktif dan berperan resepsionis.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
            'receptionist_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Receptionist->value)->where('is_active', true),
            ],
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
            'label.*' => 'Label maksimal 100 karakter.',
            'receptionist_id.*' => 'Pilih akun resepsionis yang aktif.',
        ];
    }
}
