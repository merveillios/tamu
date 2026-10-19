<?php

namespace App\Http\Requests;

use App\Models\PurposeCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah dan ubah kategori keperluan (KAT-1, Bab 11.3).
 */
class PurposeCategoryRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Merapikan nama dan mengisi urutan bawaan 0.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name'))),
            'sort_order' => $this->input('sort_order') ?? 0,
        ]);
    }

    /**
     * Nama unik per lokasi; departemen wajib dan harus aktif di lokasi yang sama.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $locationId = $this->locationId();

        return [
            'name' => [
                'required', 'string', 'min:2', 'max:150',
                Rule::unique('purpose_categories', 'name')->where('location_id', $locationId)->ignore($this->route('id')),
            ],
            'suggested_department_id' => [
                'required', 'integer',
                Rule::exists('departments', 'id')->where('location_id', $locationId)->where('is_active', true),
            ],
            'sort_order' => ['integer', 'min:0', 'max:999'],
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
            'name.unique' => 'Pertanyaan ini sudah ada di lokasi ini.',
            'name.*' => 'Pertanyaan wajib diisi (2–150 karakter).',
            'suggested_department_id.*' => 'Pilih departemen yang aktif di lokasi ini.',
            'sort_order.*' => 'Urutan tampil berupa angka 0–999.',
        ];
    }

    /**
     * Lokasi kategori: dari kategori yang diubah, atau dari parameter location_id saat menambah.
     */
    private function locationId(): ?int
    {
        $categoryId = $this->route('id');

        if ($categoryId !== null) {
            return PurposeCategory::query()->whereKey($categoryId)->value('location_id');
        }

        return (int) $this->query('location_id') ?: null;
    }
}
