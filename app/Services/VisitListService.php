<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Models\GuestVisit;
use App\Models\User;
use App\Rules\IndonesianPhone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Query dan bentuk data daftar Tamu Aktif (RSP-1, ADM-1) dan Penugasan PIC (PIC-1).
 *
 * Daftar tidak memuat telepon, alamat, email, maupun tanda tangan (Bab 12.5).
 */
class VisitListService
{
    /**
     * Nilai filter label status di query string (Bab 10.3).
     */
    public const STATUS_FILTERS = ['menunggu', 'menunggu_pic', 'direview'];

    private const RELATIONS = [
        'location:id,name',
        'qrCode:id,label',
        'suggestedDepartment:id,name',
        'assignedDepartment:id,name',
        'servedBy:id,name',
    ];

    /**
     * Kunjungan waiting dan processing dalam cakupan, urut dari yang datang paling awal.
     *
     * @param  array{q?: string|null, status?: string|null, location_id?: int|null}  $filters
     */
    public function activeVisits(User $user, array $filters): LengthAwarePaginator
    {
        return $this->filteredActive($user, $filters)
            ->tap(fn (Builder $query) => $this->applyStatusFilter($query, $filters['status'] ?? null))
            ->with(self::RELATIONS)
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->paginate(25);
    }

    /**
     * Jumlah kunjungan per label status dengan filter pencarian dan lokasi yang sama.
     *
     * @param  array{q?: string|null, location_id?: int|null}  $filters
     * @return array<string, int>
     */
    public function statusCounts(User $user, array $filters): array
    {
        $counts = [];

        foreach (self::STATUS_FILTERS as $status) {
            $counts[$status] = $this->filteredActive($user, $filters)
                ->tap(fn (Builder $query) => $this->applyStatusFilter($query, $status))
                ->count();
        }

        return $counts;
    }

    /**
     * Penugasan departemen PIC: waiting dan processing, urut dari yang datang paling awal.
     *
     * @return Collection<int, GuestVisit>
     */
    public function picAssignments(User $pic): Collection
    {
        return GuestVisit::query()
            ->forUser($pic)
            ->whereIn('status', [VisitStatus::Waiting, VisitStatus::Processing])
            ->with(self::RELATIONS)
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Jumlah penugasan baru (waiting) untuk badge menu PIC (PIC-4).
     */
    public function newAssignmentCount(User $pic): int
    {
        return GuestVisit::query()->forUser($pic)->where('status', VisitStatus::Waiting)->count();
    }

    /**
     * Bentuk satu baris untuk JSON polling beserta aksi yang boleh dilakukan akun.
     *
     * @return array<string, mixed>
     */
    public function row(GuestVisit $visit, User $viewer): array
    {
        $waitingMinutes = (int) $visit->check_in_at->diffInMinutes(now());
        $status = $visit->display_status;
        $isWaiting = $visit->status === VisitStatus::Waiting;

        return [
            'id' => $visit->id,
            'number' => $visit->daily_number,
            'name' => $visit->name,
            'organization' => $visit->organization,
            'vulnerable_group' => $visit->vulnerable_group->label(),
            'category' => $visit->purpose_category_name,
            'purpose' => $visit->purpose,
            'suggested_department' => $visit->suggestedDepartment?->name,
            'assigned_department' => $visit->assignedDepartment?->name,
            'served_by' => $visit->servedBy?->name,
            'location' => $visit->location->name,
            'qr_label' => $visit->qrCode->label ?? 'QR #'.$visit->qr_code_id,
            'check_in' => $visit->check_in_at->isToday() ? $visit->check_in_at->format('H:i') : $visit->check_in_at->format('d/m H:i'),
            'waiting_minutes' => $waitingMinutes,
            'waiting_long' => $isWaiting && $waitingMinutes > config('sitamu.waiting_alert_minutes'),
            'status' => $visit->status->value,
            'status_label' => $status['label'],
            'status_badge' => $status['badge'],
            'detail_url' => route('admin.visits.show', $visit->id),
            'actions' => [
                'assign' => $viewer->role === UserRole::Receptionist && $isWaiting,
                'assign_label' => $visit->assigned_department_id === null ? 'Tugaskan' : 'Tugaskan ulang',
                'cancel' => $viewer->hasRole(UserRole::Receptionist, UserRole::Admin, UserRole::SuperAdmin),
                'accept' => $viewer->role === UserRole::Pic && $isWaiting,
                'complete' => $viewer->role === UserRole::Pic && $visit->status === VisitStatus::Processing,
            ],
        ];
    }

    /**
     * Query dasar kunjungan aktif dengan cakupan, pencarian, dan filter lokasi.
     *
     * @param  array{q?: string|null, location_id?: int|null}  $filters
     */
    private function filteredActive(User $user, array $filters): Builder
    {
        $search = trim((string) ($filters['q'] ?? ''));

        return GuestVisit::query()
            ->forUser($user)
            ->whereIn('status', [VisitStatus::Waiting, VisitStatus::Processing])
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->when($filters['location_id'] ?? null, fn (Builder $query, int $locationId) => $query->where('location_id', $locationId));
    }

    /**
     * Pencarian pada nama, instansi, dan nomor telepon (RSP-6). Nomor yang diketik
     * berawalan 0 juga dicocokkan dengan format tersimpan berawalan 62.
     */
    private function applySearch(Builder $query, string $search): void
    {
        $phone = IndonesianPhone::normalize($search);

        $query->where(function (Builder $inner) use ($search, $phone) {
            $inner->where('name', 'like', "%{$search}%")
                ->orWhere('organization', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");

            if ($phone !== null) {
                $inner->orWhere('phone', 'like', "%{$phone}%");
            }
        });
    }

    /**
     * Filter label status: Menunggu, Menunggu PIC, atau Sedang direview.
     */
    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        match ($status) {
            'menunggu' => $query->where('status', VisitStatus::Waiting)->whereNull('assigned_department_id'),
            'menunggu_pic' => $query->where('status', VisitStatus::Waiting)->whereNotNull('assigned_department_id'),
            'direview' => $query->where('status', VisitStatus::Processing),
            default => null,
        };
    }
}
