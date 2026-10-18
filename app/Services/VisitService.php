<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Models\GuestVisit;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\VisitSignature;
use App\Models\VisitStatusLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aturan pencatatan dan perubahan status kunjungan (PRD Bab 7).
 */
class VisitService
{
    /**
     * Menyimpan kunjungan dari formulir tamu dalam satu transaksi (TAM-4):
     * nomor urut, kunjungan, tanda tangan, log status awal, lalu last_used_at QR.
     *
     * @param  array<string, mixed>  $data  data tervalidasi: kategori, rincian, data diri
     */
    public function recordGuestVisit(
        QrCode $qr,
        PurposeCategory $category,
        array $data,
        string $email,
        Carbon $emailVerifiedAt,
        string $signaturePng,
        ?string $ipAddress,
    ): GuestVisit {
        return DB::transaction(function () use ($qr, $category, $data, $email, $emailVerifiedAt, $signaturePng, $ipAddress) {
            $now = now();
            $visitDate = $now->toDateString();

            $visit = GuestVisit::create([
                'location_id' => $qr->location_id,
                'qr_code_id' => $qr->id,
                'visit_date' => $visitDate,
                'daily_number' => $this->nextDailyNumber($qr->location_id, $visitDate),
                'email' => $email,
                'email_verified_at' => $emailVerifiedAt,
                'purpose_category_id' => $category->id,
                'purpose_category_name' => $category->name,
                'suggested_department_id' => $category->suggested_department_id,
                'purpose' => $data['purpose'],
                'vulnerable_group' => $data['vulnerable_group'],
                'name' => $data['name'],
                'organization' => $data['organization'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'status' => VisitStatus::Waiting,
                'check_in_at' => $now,
                'consent_at' => $now,
                'ip_address' => $ipAddress,
            ]);

            VisitSignature::create([
                'visit_id' => $visit->id,
                'image' => $signaturePng,
                'mime_type' => 'image/png',
                'size_bytes' => strlen($signaturePng),
                'sha256' => hash('sha256', $signaturePng),
            ]);

            VisitStatusLog::create([
                'visit_id' => $visit->id,
                'location_id' => $visit->location_id,
                'from_status' => null,
                'to_status' => VisitStatus::Waiting,
                'changed_by' => null,
            ]);

            $qr->forceFill(['last_used_at' => $now])->save();

            return $visit;
        });
    }

    /**
     * Mengambil nomor urut harian per lokasi secara atomik (Bab 9.3). Harus dipanggil di
     * dalam transaksi agar nomor tidak terpakai bila penyimpanan gagal.
     */
    private function nextDailyNumber(int $locationId, string $visitDate): int
    {
        DB::insert(
            'INSERT INTO visit_counters (location_id, visit_date, last_number) VALUES (?, ?, LAST_INSERT_ID(1)) '
            .'ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)',
            [$locationId, $visitDate],
        );

        return (int) DB::selectOne('SELECT LAST_INSERT_ID() AS n')->n;
    }
}
