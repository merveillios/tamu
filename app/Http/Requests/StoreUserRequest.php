<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validasi pembuatan akun staf oleh Super Admin (USR-1, PRD Bab 11.3).
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Route sudah dibatasi role:super_admin.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::SuperAdmin) ?? false;
    }

    /**
     * Merapikan input dan membuang field yang tidak berlaku untuk peran terpilih,
     * sehingga departemen hanya tersimpan untuk PIC dan lokasi hanya untuk Admin.
     */
    protected function prepareForValidation(): void
    {
        $role = $this->input('role');

        $this->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name'))),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'department_id' => $role === UserRole::Pic->value ? $this->input('department_id') : null,
            'location_ids' => $role === UserRole::Admin->value ? (array) $this->input('location_ids', []) : [],
        ]);
    }

    /**
     * Aturan validasi akun staf.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'max:150', 'email:rfc', Rule::unique('users', 'email')->ignore($this->route('id'))],
            'role' => ['required', Rule::enum(UserRole::class)],
            'department_id' => [
                'nullable',
                'required_if:role,'.UserRole::Pic->value,
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'location_ids' => ['array'],
            'location_ids.*' => ['integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'password' => $this->passwordRules(),
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
            'name.*' => 'Nama akun wajib diisi (2–100 karakter).',
            'email.unique' => 'Email sudah digunakan.',
            'email.*' => 'Email tidak valid.',
            'role.*' => 'Pilih peran akun.',
            'department_id.*' => 'Pilih departemen aktif untuk akun PIC.',
            'location_ids.*' => 'Lokasi tidak valid.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.*' => 'Password minimal 8 karakter.',
        ];
    }

    /**
     * Password wajib saat membuat akun.
     *
     * @return array<int, string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', 'min:8', 'max:255', 'confirmed'];
    }
}
