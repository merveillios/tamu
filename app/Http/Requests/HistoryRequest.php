<?php

namespace App\Http\Requests;

use App\Enums\VulnerableGroup;
use App\Services\HistoryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi filter History dan ekspor; filter tersimpan di query string (HIS-2).
 */
class HistoryRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan filter dan format ekspor.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'period' => ['nullable', Rule::in(array_keys(HistoryService::PERIODS))],
            'status' => ['nullable', Rule::in(array_keys(HistoryService::STATUS_FILTERS))],
            'location_id' => ['nullable', 'integer'],
            'purpose_category_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'vulnerable_group' => ['nullable', Rule::enum(VulnerableGroup::class)],
            'format' => ['nullable', 'in:csv,xlsx,pdf'],
            'page' => ['nullable', 'integer', 'min:1'],
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
            'from.*' => 'Tanggal awal tidak valid.',
            'to.*' => 'Tanggal akhir tidak valid atau sebelum tanggal awal.',
            'format.*' => 'Format ekspor hanya CSV, Excel, atau PDF.',
        ];
    }

    /**
     * Filter tanpa page dan format, untuk query dan audit log.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter(
            collect($this->validated())->except(['page', 'format'])->all(),
            fn ($value) => $value !== null && $value !== '',
        );
    }
}
