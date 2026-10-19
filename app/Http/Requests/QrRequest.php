<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi buat QR dan ganti akun resepsionis QR (QR-2, QR-6, Bab 11.3).
 * Lokasi dan departemen hanya divalidasi saat membuat QR.
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
     * Akun resepsionis wajib aktif dan berperan resepsionis. Saat membuat QR, lokasi
     * wajib dipilih dan departemen wajib departemen aktif di lokasi tersebut.
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
            $rules['department_id'] = [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('location_id', (int) $this->input('location_id'))->where('is_active', true),
            ];
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
            'department_id.*' => 'Pilih departemen aktif di lokasi yang dipilih.',
            'label.*' => 'Label maksimal 100 karakter.',
            'receptionist_id.*' => 'Pilih akun resepsionis yang aktif.',
        ];
    }
}
