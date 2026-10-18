<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Models\GuestVisit;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Angka dan daftar dashboard Admin dan Super Admin (DSH-1 sampai DSH-4).
 *
 * Seluruh angka dihitung dari database lewat AccessScope.
 */
class DashboardService
{
    private const RELATIONS = ['location:id,name', 'assignedDepartment:id,name', 'servedBy:id,name'];

    /**
     * Lima angka per cakupan atau per lokasi (DSH-1). Hitungan waiting mencakup semua tanggal.
     *
     * @return array{unassigned: int, waiting_pic: int, processing: int, completed_today: int, today: int}
     */
    public function counts(User $user, ?int $locationId = null): array
    {
        $startOfDay = now()->startOfDay();

        return [
            'unassigned' => $this->base($user, $locationId)->where('status', VisitStatus::Waiting)->whereNull('assigned_department_id')->count(),
            'waiting_pic' => $this->base($user, $locationId)->where('status', VisitStatus::Waiting)->whereNotNull('assigned_department_id')->count(),
            'processing' => $this->base($user, $locationId)->where('status', VisitStatus::Processing)->count(),
            'completed_today' => $this->base($user, $locationId)->where('status', VisitStatus::Completed)->where('completed_at', '>=', $startOfDay)->count(),
            'today' => $this->base($user, $locationId)->where('check_in_at', '>=', $startOfDay)->count(),
        ];
    }

    /**
     * Kartu ringkasan Super Admin (DSH-3).
     *
     * @return array{total: int, active_locations: int, today: int, waiting: int}
     */
    public function overview(User $user): array
    {
        return [
            'total' => $this->base($user)->count(),
            'active_locations' => Location::query()->forUser($user)->where('is_active', true)->count(),
            'today' => $this->base($user)->where('check_in_at', '>=', now()->startOfDay())->count(),
            'waiting' => $this->base($user)->where('status', VisitStatus::Waiting)->count(),
        ];
    }

    /**
     * Tabel angka per lokasi dalam cakupan (DSH-3).
     *
     * @return Collection<int, array{location: Location, counts: array<string, int>}>
     */
    public function perLocation(User $user): Collection
    {
        return Location::query()->forUser($user)->orderBy('name')->get(['id', 'name', 'is_active'])
            ->map(fn (Location $location) => ['location' => $location, 'counts' => $this->counts($user, $location->id)]);
    }

    /**
     * Jumlah kunjungan per hari selama 14 hari terakhir untuk grafik batang (DSH-3).
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function dailyVisits(User $user, int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $totals = $this->base($user)
            ->where('check_in_at', '>=', $start)
            ->selectRaw('visit_date, COUNT(*) AS total')
            ->groupBy('visit_date')
            ->pluck('total', 'visit_date')
            ->mapWithKeys(fn ($total, $date) => [substr((string) $date, 0, 10) => (int) $total]);

        $labels = [];
        $values = [];

        for ($day = $start->copy(); $day->lte(now()); $day->addDay()) {
            $labels[] = $day->format('d/m');
            $values[] = $totals->get($day->toDateString(), 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Sepuluh kunjungan terbaru dalam cakupan (DSH-3).
     *
     * @return Collection<int, GuestVisit>
     */
    public function latestVisits(User $user, int $limit = 10): Collection
    {
        return $this->base($user)->with(self::RELATIONS)->orderByDesc('check_in_at')->orderByDesc('id')->limit($limit)->get();
    }

    /**
     * Tamu Aktif ringkas: maksimal 10 baris teratas, urut dari yang datang paling awal (DSH-2).
     *
     * @return Collection<int, GuestVisit>
     */
    public function activePreview(User $user, int $limit = 10): Collection
    {
        return $this->base($user)
            ->whereIn('status', [VisitStatus::Waiting, VisitStatus::Processing])
            ->with(self::RELATIONS)
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Query kunjungan dalam cakupan, opsional untuk satu lokasi.
     */
    private function base(User $user, ?int $locationId = null): Builder
    {
        return GuestVisit::query()
            ->forUser($user)
            ->when($locationId, fn (Builder $query) => $query->where('location_id', $locationId));
    }
}
