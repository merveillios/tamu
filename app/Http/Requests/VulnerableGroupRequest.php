<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah dan ubah kelompok rentan (VUL-1, Bab 11.3).
 */
class VulnerableGroupRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Merapikan nama; prioritas kosong berarti tidak didahulukan, urutan kosong berarti 0.
     */
    protected function prepareForValidation(): void
    {
        $priority = trim((string) $this->input('priority'));

        $this->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name'))),
            'priority' => $priority === '' ? null : $priority,
            'sort_order' => trim((string) $this->input('sort_order')) === '' ? 0 : $this->input('sort_order'),
        ]);
    }

    /**
     * Nama unik (tidak peka huruf besar/kecil), prioritas 1–99 atau kosong, urutan 0–999.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:2', 'max:100',
                Rule::unique('vulnerable_groups', 'name')->ignore($this->route('id')),
            ],
            'priority' => ['nullable', 'integer', 'min:1', 'max:99'],
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
            'name.unique' => 'Kelompok rentan dengan nama ini sudah ada.',
            'name.*' => 'Nama kelompok rentan wajib diisi (2–100 karakter).',
            'priority.*' => 'Prioritas antrean berupa angka 1–99, atau kosongkan bila tidak didahulukan.',
            'sort_order.*' => 'Urutan tampil berupa angka 0–999.',
        ];
    }
}
