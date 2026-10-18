<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Enums\VulnerableGroup;
use App\Models\Department;
use App\Models\GuestVisit;
use App\Models\Location;
use App\Models\PurposeCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Query History Kunjungan yang dipakai bersama halaman History dan ekspor (HIS-1 sampai HIS-4).
 */
class HistoryService
{
    /**
     * Pilihan filter status: Selesai, Dibatalkan, dan Sedang direview (HIS-1).
     */
    public const STATUS_FILTERS = [
        'selesai' => VisitStatus::Completed,
        'dibatalkan' => VisitStatus::Cancelled,
        'direview' => VisitStatus::Processing,
    ];

    /**
     * Preset periode (HIS-2).
     */
    public const PERIODS = [
        'hari_ini' => 'Hari ini',
        '7_hari' => '7 hari terakhir',
        'bulan_ini' => 'Bulan ini',
    ];

    public function __construct(private readonly AccessScope $scope)
    {
    }

    /**
     * Query kunjungan History dalam cakupan sesuai filter, urut terbaru.
     *
     * @param  array<string, mixed>  $filters  hasil validasi HistoryRequest
     */
    public function query(User $user, array $filters): Builder
    {
        $status = self::STATUS_FILTERS[$filters['status'] ?? ''] ?? null;
        [$from, $to] = $this->dateRange($filters);
        $search = trim((string) ($filters['q'] ?? ''));

        return GuestVisit::query()
            ->forUser($user)
            ->when(
                $status !== null,
                fn (Builder $query) => $query->where('status', $status),
                fn (Builder $query) => $query->whereIn('status', [VisitStatus::Completed, VisitStatus::Cancelled]),
            )
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('organization', 'like', "%{$search}%")
                ->orWhere('purpose', 'like', "%{$search}%")))
            ->when($from, fn (Builder $query) => $query->where('check_in_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('check_in_at', '<=', $to))
            ->when($filters['location_id'] ?? null, fn (Builder $query, $id) => $query->where('location_id', $id))
            ->when($filters['purpose_category_id'] ?? null, fn (Builder $query, $id) => $query->where('purpose_category_id', $id))
            ->when($filters['department_id'] ?? null, fn (Builder $query, $id) => $query->where('assigned_department_id', $id))
            ->when($filters['vulnerable_group'] ?? null, fn (Builder $query, $group) => $query->where('vulnerable_group', $group))
            ->orderByDesc('check_in_at')
            ->orderByDesc('id');
    }

    /**
     * Pilihan dropdown filter dalam cakupan akun, termasuk kategori nonaktif (HIS-3).
     *
     * @return array<string, Collection>
     */
    public function filterOptions(User $user): array
    {
        $locations = Location::query()->forUser($user)->orderBy('name')->get(['id', 'name']);
        $locationIds = $locations->pluck('id');

        return [
            'locations' => $locations,
            'categories' => PurposeCategory::query()->whereIn('location_id', $locationIds)
                ->with('location:id,name')->orderBy('name')->get(['id', 'name', 'location_id', 'is_active']),
            'departments' => Department::query()->whereIn('location_id', $locationIds)
                ->with('location:id,name')->orderBy('name')->get(['id', 'name', 'location_id']),
            'vulnerableGroups' => collect(VulnerableGroup::cases()),
        ];
    }

    /**
     * Memastikan lokasi, kategori, dan departemen di filter berada dalam cakupan (Bab 12.2).
     *
     * @param  array<string, mixed>  $filters
     */
    public function ensureFiltersInScope(User $user, array $filters): void
    {
        if (! empty($filters['location_id'])) {
            $this->scope->findOrDeny(Location::class, $filters['location_id'], $user);
        }

        $locationIds = $this->scope->locationIds($user);

        foreach (['purpose_category_id' => PurposeCategory::class, 'department_id' => Department::class] as $field => $model) {
            if (empty($filters[$field])) {
                continue;
            }

            $inScope = $model::query()->whereKey($filters[$field])
                ->when($locationIds !== null, fn (Builder $query) => $query->whereIn('location_id', $locationIds))
                ->exists();

            abort_unless($inScope, 404);
        }
    }

    /**
     * Rentang check_in_at dari preset periode atau tanggal dari–sampai.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    public function dateRange(array $filters): array
    {
        return match ($filters['period'] ?? null) {
            'hari_ini' => [now()->startOfDay(), now()->endOfDay()],
            '7_hari' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'bulan_ini' => [now()->startOfMonth(), now()->endOfDay()],
            default => [
                empty($filters['from']) ? null : Carbon::parse($filters['from'])->startOfDay(),
                empty($filters['to']) ? null : Carbon::parse($filters['to'])->endOfDay(),
            ],
        };
    }
}
