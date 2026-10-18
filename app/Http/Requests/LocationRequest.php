<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah dan ubah lokasi (LOK-1).
 */
class LocationRequest extends FormRequest
{
    /**
     * Hanya Super Admin yang mengelola lokasi.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::SuperAdmin) ?? false;
    }

    /**
     * Merapikan spasi berlebih pada nama.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name')))]);
    }

    /**
     * Nama unik tanpa membedakan huruf besar/kecil (collation utf8mb4_unicode_ci),
     * termasuk lokasi yang sudah di-soft delete.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('locations', 'name')->ignore($this->route('id'))],
            'description' => ['nullable', 'string', 'max:255'],
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
            'name.unique' => 'Nama lokasi sudah digunakan',
            'name.*' => 'Nama lokasi wajib diisi (2–100 karakter).',
            'description.*' => 'Deskripsi maksimal 255 karakter.',
        ];
    }
}
