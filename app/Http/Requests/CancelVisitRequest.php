<?php

namespace App\Http\Requests;

use App\Services\VisitService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pembatalan kunjungan (RSP-4, ADM-2, Bab 11.2).
 */
class CancelVisitRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Alasan wajib dipilih; keterangan wajib bila alasan Lainnya.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(array_keys(VisitService::CANCEL_REASONS))],
            'reason_text' => ['nullable', 'required_if:reason,other', 'string', 'max:255'],
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
            'reason.*' => 'Pilih alasan pembatalan.',
            'reason_text.*' => 'Tulis alasan pembatalan (maks. 255 karakter).',
        ];
    }
}
