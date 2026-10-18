<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Exceptions\VisitActionException;
use App\Models\Department;
use App\Models\GuestVisit;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\VisitAssignment;
use App\Models\VisitSignature;
use App\Models\VisitStatusLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aturan pencatatan, penugasan, dan perubahan status kunjungan (PRD Bab 7).
 *
 * Setiap perubahan memakai update bersyarat di dalam transaksi sehingga dua
 * petugas tidak dapat memproses kunjungan yang sama secara bersamaan (Bab 7.2).
 */
class VisitService
{
    /**
     * Pilihan alasan pembatalan (RSP-4, Bab 11.2).
     */
    public const CANCEL_REASONS = [
        'not_present' => 'Tamu tidak hadir/pergi',
        'wrong_location' => 'Salah lokasi',
        'duplicate' => 'Data ganda',
        'other' => 'Lainnya',
    ];

    public function __construct(
        private readonly AccessScope $scope,
        private readonly AuditService $audit,
    ) {
    }

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

            $this->logStatus($visit, null, VisitStatus::Waiting, null);

            $qr->forceFill(['last_used_at' => $now])->save();

            return $visit;
        });
    }

    /**
     * Daftar departemen yang dapat dipilih untuk sebuah kunjungan: aktif, di lokasi
     * kunjungan, punya akun PIC aktif, dan punya minimal satu petugas aktif (RSP-2).
     *
     * @return \Illuminate\Support\Collection<int, Department>
     */
    public function assignableDepartments(GuestVisit $visit)
    {
        return Department::query()
            ->where('location_id', $visit->location_id)
            ->where('is_active', true)
            ->whereHas('picUsers', fn ($query) => $query->where('role', UserRole::Pic)->where('is_active', true))
            ->whereHas('staffMembers', fn ($query) => $query->where('is_active', true))
            ->with(['staffMembers' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    /**
     * Menugaskan atau menugaskan ulang kunjungan ke departemen dan orang yang ditemui (RSP-2, RSP-3).
     */
    public function assign(GuestVisit $visit, int $departmentId, int $staffMemberId, User $receptionist): GuestVisit
    {
        $this->ensureStatus($visit, VisitStatus::Waiting, 'Kunjungan ini sudah diterima PIC. Daftar akan dimuat ulang.');
        $this->ensureAssignable($visit, $departmentId, $staffMemberId);

        DB::transaction(function () use ($visit, $departmentId, $staffMemberId, $receptionist) {
            $now = now();

            $updated = GuestVisit::query()->forUser($receptionist)->whereKey($visit->id)
                ->where('status', VisitStatus::Waiting)
                ->update([
                    'assigned_department_id' => $departmentId,
                    'assigned_at' => $now,
                    'assigned_by' => $receptionist->id,
                    'served_by_staff_id' => $staffMemberId,
                ]);

            if ($updated === 0) {
                throw VisitActionException::conflict('Kunjungan ini sudah diterima PIC. Daftar akan dimuat ulang.');
            }

            VisitAssignment::create([
                'visit_id' => $visit->id,
                'department_id' => $departmentId,
                'assigned_by' => $receptionist->id,
                'assigned_at' => $now,
                'staff_member_id' => $staffMemberId,
            ]);
        });

        return $visit->refresh();
    }

    /**
     * Mencari kunjungan untuk aksi PIC. Kunjungan yang pernah ditugaskan ke departemen
     * PIC lalu dialihkan mendapat 409 tanpa audit access.denied (Bab 7.2).
     */
    public function findForPic(int $visitId, User $pic): GuestVisit
    {
        $visit = GuestVisit::query()->forUser($pic)->whereKey($visitId)->first();

        if ($visit !== null) {
            return $visit;
        }

        $wasAssignedHere = $pic->department_id !== null && VisitAssignment::query()
            ->where('visit_id', $visitId)
            ->where('department_id', $pic->department_id)
            ->exists();

        if ($wasAssignedHere) {
            throw VisitActionException::conflict('Kunjungan ini sudah dialihkan. Daftar akan dimuat ulang.');
        }

        return $this->scope->findOrDeny(GuestVisit::class, $visitId, $pic);
    }

    /**
     * PIC menerima penugasan: waiting → processing (PIC-2).
     */
    public function accept(GuestVisit $visit, User $pic): GuestVisit
    {
        $this->ensureStatus($visit, VisitStatus::Waiting);

        DB::transaction(function () use ($visit, $pic) {
            $updated = GuestVisit::query()->forUser($pic)->whereKey($visit->id)
                ->where('status', VisitStatus::Waiting)
                ->where('assigned_department_id', $pic->department_id)
                ->update([
                    'status' => VisitStatus::Processing,
                    'processed_at' => now(),
                    'processed_by' => $pic->id,
                ]);

            if ($updated === 0) {
                throw VisitActionException::conflict();
            }

            $this->logStatus($visit, VisitStatus::Waiting, VisitStatus::Processing, $pic, $visit->served_by_staff_id);
        });

        return $visit->refresh();
    }

    /**
     * PIC menulis hasil dan menyelesaikan kunjungan: processing → completed (PIC-3).
     * Bila kunjungan pernah dibuka kembali, hasil lama disimpan di catatan log (Bab 7.3).
     */
    public function complete(GuestVisit $visit, string $result, User $pic): GuestVisit
    {
        $this->ensureStatus($visit, VisitStatus::Processing);

        DB::transaction(function () use ($visit, $result, $pic) {
            $updated = GuestVisit::query()->forUser($pic)->whereKey($visit->id)
                ->where('status', VisitStatus::Processing)
                ->where('assigned_department_id', $pic->department_id)
                ->update([
                    'status' => VisitStatus::Completed,
                    'result' => $result,
                    'completed_at' => now(),
                    'closed_by' => $pic->id,
                ]);

            if ($updated === 0) {
                throw VisitActionException::conflict();
            }

            $note = $visit->result !== null && $visit->result !== $result ? 'Hasil sebelumnya: '.$visit->result : null;
            $this->logStatus($visit, VisitStatus::Processing, VisitStatus::Completed, $pic, $visit->served_by_staff_id, $note);
        });

        return $visit->refresh();
    }

    /**
     * Membatalkan kunjungan waiting atau processing dengan alasan (RSP-4, ADM-2).
     */
    public function cancel(GuestVisit $visit, string $reason, ?string $reasonText, User $actor): GuestVisit
    {
        if (! $visit->status->isActive()) {
            throw VisitActionException::conflict();
        }

        $reasonLabel = $reason === 'other' ? 'Lainnya: '.$reasonText : self::CANCEL_REASONS[$reason];
        $from = $visit->status;

        DB::transaction(function () use ($visit, $reasonLabel, $from, $actor) {
            $updated = GuestVisit::query()->forUser($actor)->whereKey($visit->id)
                ->where('status', $from)
                ->update([
                    'status' => VisitStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reasonLabel,
                    'closed_by' => $actor->id,
                ]);

            if ($updated === 0) {
                throw VisitActionException::conflict();
            }

            $this->logStatus($visit, $from, VisitStatus::Cancelled, $actor, null, $reasonLabel);
        });

        return $visit->refresh();
    }

    /**
     * Admin membuka kembali kunjungan Selesai atau Dibatalkan ke status sebelum ditutup (ADM-3, Bab 7.3).
     */
    public function reopen(GuestVisit $visit, string $note, User $admin): GuestVisit
    {
        if ($visit->status->isActive()) {
            throw VisitActionException::invalidTransition('Hanya kunjungan Selesai atau Dibatalkan yang dapat dibuka kembali.');
        }

        $closedFrom = $visit->status;
        // value() pada Eloquent menerapkan cast, sehingga hasilnya sudah berupa VisitStatus.
        $target = VisitStatusLog::query()
            ->where('visit_id', $visit->id)
            ->where('to_status', $closedFrom)
            ->latest('id')
            ->value('from_status') ?? VisitStatus::Waiting;

        $previous = [
            'completed_at' => $visit->completed_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $visit->cancelled_at?->format('Y-m-d H:i:s'),
            'cancel_reason' => $visit->cancel_reason,
            'closed_by' => $visit->closed_by,
        ];

        DB::transaction(function () use ($visit, $closedFrom, $target, $previous, $note, $admin) {
            $updated = GuestVisit::query()->forUser($admin)->whereKey($visit->id)
                ->where('status', $closedFrom)
                ->update([
                    'status' => $target,
                    'completed_at' => null,
                    'cancelled_at' => null,
                    'cancel_reason' => null,
                    'closed_by' => null,
                ]);

            if ($updated === 0) {
                throw VisitActionException::conflict();
            }

            $logNote = 'Dibuka kembali: '.$note.'. Sebelumnya: '.json_encode(array_filter($previous), JSON_UNESCAPED_UNICODE);
            $this->logStatus($visit, $closedFrom, $target, $admin, null, $logNote);
            $this->audit->record('visit.reopen', $visit, ['from' => $closedFrom->value, 'to' => $target->value, 'note' => $note, 'previous' => $previous], $admin);
        });

        return $visit->refresh();
    }

    /**
     * Menolak aksi bila status kunjungan tidak sesuai. Kunjungan yang sudah ditutup
     * berarti transisi tidak sah (422); selebihnya status sudah diubah petugas lain (409).
     */
    private function ensureStatus(GuestVisit $visit, VisitStatus $expected, ?string $conflictMessage = null): void
    {
        if ($visit->status === $expected) {
            return;
        }

        if (! $visit->status->isActive()) {
            throw VisitActionException::invalidTransition();
        }

        throw $conflictMessage === null ? VisitActionException::conflict() : VisitActionException::conflict($conflictMessage);
    }

    /**
     * Memastikan departemen dan orang yang ditemui boleh dipilih untuk kunjungan ini (Bab 11.2).
     */
    private function ensureAssignable(GuestVisit $visit, int $departmentId, int $staffMemberId): void
    {
        $department = $this->assignableDepartments($visit)->firstWhere('id', $departmentId);

        if ($department === null) {
            throw ValidationException::withMessages(['department_id' => 'Pilih departemen tujuan.']);
        }

        if (! $department->staffMembers->contains(fn (StaffMember $staff) => $staff->id === $staffMemberId)) {
            throw ValidationException::withMessages(['staff_member_id' => 'Pilih orang yang ditemui.']);
        }
    }

    /**
     * Mencatat satu perubahan status di visit_status_logs.
     */
    private function logStatus(
        GuestVisit $visit,
        ?VisitStatus $from,
        VisitStatus $to,
        ?User $actor,
        ?int $staffMemberId = null,
        ?string $note = null,
    ): void {
        VisitStatusLog::create([
            'visit_id' => $visit->id,
            'location_id' => $visit->location_id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $actor?->id,
            'staff_member_id' => $staffMemberId,
            'note' => $note === null ? null : mb_substr($note, 0, 2500),
        ]);
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
