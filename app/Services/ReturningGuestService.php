<?php

namespace App\Services;

use App\Models\GuestVisit;
use App\Models\VisitSignature;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Data tamu lama berdasarkan email terverifikasi (PRD Bab 9.4, TAM-15 sampai TAM-17).
 *
 * Satu-satunya tempat yang membaca guest_visits tanpa AccessScope (pengecualian A-6).
 * Email selalu berasal dari session hasil verifikasi Google, tidak pernah dari request.
 */
class ReturningGuestService
{
    /**
     * Kolom data diri yang diambil dari kunjungan terakhir (TAM-16).
     */
    public const PROFILE_FIELDS = ['vulnerable_group', 'name', 'organization', 'phone', 'address'];

    /**
     * Mengecek apakah email ini sudah punya tanda tangan dari kunjungan sebelumnya.
     */
    public function hasSignature(string $email): bool
    {
        return $this->signatureQuery($email)->exists();
    }

    /**
     * Gambar tanda tangan dari kunjungan terbaru email ini yang memiliki tanda tangan.
     * Dipakai ulang agar tamu lama cukup tanda tangan sekali pada kunjungan pertama.
     */
    public function latestSignature(string $email): ?string
    {
        return $this->signatureQuery($email)->orderByDesc('visit_id')->value('image');
    }

    /**
     * Tamu lama = pernah tercatat di lokasi mana pun dengan email yang sama.
     */
    public function isReturning(string $email): bool
    {
        return GuestVisit::query()->where('email', $email)->exists();
    }

    /**
     * Data diri dari kunjungan dengan check_in_at terbaru, atau null bila tamu baru.
     *
     * @return array<string, string>|null
     */
    public function latestProfile(string $email): ?array
    {
        $visit = GuestVisit::query()
            ->where('email', $email)
            ->orderByDesc('check_in_at')
            ->orderByDesc('id')
            ->first(self::PROFILE_FIELDS);

        if ($visit === null) {
            return null;
        }

        return [
            'vulnerable_group' => $visit->vulnerable_group->value,
            'name' => $visit->name,
            'organization' => $visit->organization,
            'phone' => $visit->phone,
            'address' => $visit->address,
        ];
    }

    /**
     * Riwayat kunjungan email ini, terbaru di atas, tanpa telepon, alamat, dan alasan batal (TAM-17).
     */
    public function history(string $email): Paginator
    {
        return GuestVisit::query()
            ->where('email', $email)
            ->select([
                'id', 'location_id', 'purpose_category_name', 'purpose', 'status', 'check_in_at',
                'assigned_department_id', 'served_by_staff_id', 'result',
            ])
            ->with(['location:id,name', 'assignedDepartment:id,name', 'servedBy:id,name'])
            ->orderByDesc('check_in_at')
            ->orderByDesc('id')
            ->simplePaginate(config('sitamu.guest.history_per_page'))
            ->withQueryString();
    }

    /**
     * Tanda tangan milik kunjungan-kunjungan email ini, tanpa JOIN ke daftar kunjungan.
     */
    private function signatureQuery(string $email): Builder
    {
        return VisitSignature::query()
            ->whereIn('visit_id', GuestVisit::query()->where('email', $email)->select('id'));
    }
}
