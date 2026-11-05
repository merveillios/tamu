<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi buat QR dan ganti akun resepsionis QR (QR-2, QR-6, Bab 11.3).
 * Lokasi hanya divalidasi saat membuat QR.
 */
class QrRequest extends FormRequest
{
    /**
     * Admin dan Super Admin mengelola QR (QR-2, PRD v0.11).
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::SuperAdmin, UserRole::Admin) ?? false;
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
     * Akun resepsionis wajib aktif dan berperan resepsionis. Saat membuat QR, lokasi wajib dipilih.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'label' => ['nullable', 'string', 'max:100'],
            'receptionist_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Receptionist->value)->where('is_active', true),
            ],
        ];

        if ($this->routeIs('admin.qr.store')) {
            $rules['location_id'] = ['required', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }

    /**
     * Pesan error Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location_id.*' => 'Pilih lokasi QR.',
            'label.*' => 'Label maksimal 100 karakter.',
            'receptionist_id.*' => 'Pilih akun resepsionis yang aktif.',
        ];
    }
}
