<?php

namespace App\Http\Requests;

use App\Rules\IndonesianPhone;
use App\Rules\ValidSignatureImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi formulir "Tambah Tamu Manual" untuk resepsionis (RSP-11, revisi HRD #11).
 *
 * Aturannya sama dengan formulir tamu (Bab 11.1), ditambah email yang diketik petugas
 * (wajib) dan pilihan pos (QR). Foto KTP dan selfie opsional dan divalidasi saat disimpan.
 */
class ManualVisitRequest extends FormRequest
{
    /**
     * Pembatasan peran sudah dilakukan middleware route; QR dicek di controller.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Merapikan spasi dan huruf email.
     */
    protected function prepareForValidation(): void
    {
        $clean = ['email' => mb_strtolower(trim((string) $this->input('email')))];

        foreach (['purpose', 'name', 'organization', 'address'] as $field) {
            $clean[$field] = preg_replace('/\s+/', ' ', trim((string) $this->input($field)));
        }

        $this->merge($clean);
    }

    /**
     * Aturan sesuai urutan field formulir.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'qr_code_id' => ['required', 'integer'],
            'email' => ['required', 'string', 'max:254', 'email:rfc'],
            'purpose' => ['required', 'string', 'min:3', 'max:500'],
            'vulnerable_group' => ['required', 'string', Rule::exists('vulnerable_groups', 'code')->where('is_active', true)],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'organization' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'max:30', new IndonesianPhone()],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'signature' => ['required', 'string', new ValidSignatureImage()],
            // Foto opsional: data URL hasil kompresi browser, atau file asli bila JavaScript mati.
            'ktp_data' => ['nullable', 'string', 'max:4000000'],
            'ktp_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'selfie_data' => ['nullable', 'string', 'max:4000000'],
            'selfie_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'consent' => ['required', 'in:1'],
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
            'qr_code_id.*' => 'Pilih pos (QR) kunjungan.',
            'email.*' => 'Email tamu wajib diisi dengan alamat yang valid.',
            'purpose.*' => 'Rincian keperluan wajib diisi (3–500 karakter).',
            'vulnerable_group.*' => 'Pilih kelompok rentan.',
            'name.*' => 'Nama lengkap wajib diisi (2–100 karakter).',
            'organization.*' => 'Instansi/perusahaan wajib diisi (2–150 karakter).',
            'phone.*' => 'Nomor telepon tidak valid. Contoh: 081234567890.',
            'address.*' => 'Alamat wajib diisi (5–255 karakter).',
            'signature.*' => 'Tanda tangan tamu wajib dibubuhkan.',
            'ktp_data.*' => 'Foto KTP terlalu besar. Coba foto lain.',
            'ktp_photo.*' => 'Foto KTP gagal diunggah. Gunakan foto JPG, PNG, atau WebP maksimal 10 MB.',
            'selfie_data.*' => 'Foto selfie terlalu besar. Coba foto lain.',
            'selfie_photo.*' => 'Foto selfie gagal diunggah. Gunakan foto JPG, PNG, atau WebP maksimal 10 MB.',
            'consent.*' => 'Pastikan tamu sudah menyetujui pemrosesan data.',
        ];
    }

    /**
     * Data kunjungan dengan nomor telepon dalam format 62.
     *
     * @return array<string, mixed>
     */
    public function visitData(): array
    {
        return array_merge(
            $this->safe()->only(['email', 'purpose', 'vulnerable_group', 'name', 'organization', 'address']),
            ['phone' => IndonesianPhone::normalize($this->validated('phone'))],
        );
    }
}
