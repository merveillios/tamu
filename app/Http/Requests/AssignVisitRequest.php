<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi tugaskan atau tugaskan ulang kunjungan (RSP-2, RSP-3, Bab 11.2).
 * Kecocokan departemen dan petugas dengan lokasi kunjungan dicek di VisitService.
 */
class AssignVisitRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Departemen tujuan dan orang yang ditemui wajib dipilih.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer'],
            'staff_member_id' => ['required', 'integer'],
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
            'department_id.*' => 'Pilih departemen tujuan.',
            'staff_member_id.*' => 'Pilih orang yang ditemui.',
        ];
    }
}
