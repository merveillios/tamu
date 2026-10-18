<?php

namespace App\Http\Requests;

use App\Enums\VulnerableGroup;
use App\Models\QrCode;
use App\Rules\IndonesianPhone;
use App\Rules\ValidSignatureImage;
use App\Services\GuestQrResolver;
use App\Services\GuestSessionService;
use App\Services\ReturningGuestService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi formulir buku tamu (TAM-3, TAM-7, TAM-9, TAM-16, PRD Bab 11.1).
 *
 * Email tidak pernah dibaca dari input; untuk tamu lama, data diri diambil dari
 * database dan input browser untuk field tersebut diabaikan.
 */
class GuestVisitRequest extends FormRequest
{
    /**
     * Formulir tamu tidak memerlukan login.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Merapikan spasi, lalu mengganti data diri tamu lama dengan data dari database.
     */
    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach (['purpose', 'name', 'organization', 'address'] as $field) {
            $clean[$field] = preg_replace('/\s+/', ' ', trim((string) $this->input($field)));
        }

        $this->merge($clean);

        $profile = $this->returningProfile();

        if ($profile !== null) {
            $this->merge($profile);
        }
    }

    /**
     * Aturan sesuai urutan field formulir.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'purpose_category_id' => [
                'required', 'integer',
                Rule::exists('purpose_categories', 'id')
                    ->where('location_id', $this->qr()->location_id)
                    ->where('is_active', true),
            ],
            'purpose' => ['required', 'string', 'min:3', 'max:500'],
            'vulnerable_group' => ['required', Rule::enum(VulnerableGroup::class)],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'organization' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'max:30', new IndonesianPhone()],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'signature' => ['required', 'string', new ValidSignatureImage()],
            'consent' => ['required', 'in:1'],
        ];
    }

    /**
     * Pesan error Bahasa Indonesia sesuai Bab 11.1.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'purpose_category_id.*' => 'Pilih kategori keperluan.',
            'purpose.*' => 'Rincian keperluan wajib diisi (3–500 karakter).',
            'vulnerable_group.*' => 'Pilih kelompok rentan.',
            'name.*' => 'Nama lengkap wajib diisi (2–100 karakter).',
            'organization.*' => 'Instansi/perusahaan wajib diisi (2–150 karakter).',
            'phone.*' => 'Nomor telepon tidak valid. Contoh: 081234567890.',
            'address.*' => 'Alamat wajib diisi (5–255 karakter).',
            'signature.*' => 'Tanda tangan wajib dibubuhkan.',
            'consent.*' => 'Anda perlu menyetujui pernyataan pemrosesan data.',
        ];
    }

    /**
     * Nomor telepon disimpan dalam format 62 setelah lolos validasi.
     */
    protected function passedValidation(): void
    {
        $this->merge(['phone' => IndonesianPhone::normalize($this->input('phone'))]);
    }

    /**
     * Data valid dengan nomor telepon yang sudah dinormalisasi.
     *
     * @return array<string, mixed>
     */
    public function guestData(): array
    {
        return array_merge($this->validated(), ['phone' => $this->input('phone')]);
    }

    /**
     * QR dari token di URL, divalidasi ulang saat POST (TAM-4).
     */
    private function qr(): QrCode
    {
        return app(GuestQrResolver::class)->resolve((string) $this->route('token'));
    }

    /**
     * Data diri kunjungan terakhir bila email di session milik tamu lama.
     *
     * @return array<string, string>|null
     */
    private function returningProfile(): ?array
    {
        $email = app(GuestSessionService::class)->verifiedEmail($this->qr());

        return $email === null ? null : app(ReturningGuestService::class)->latestProfile($email);
    }
}
