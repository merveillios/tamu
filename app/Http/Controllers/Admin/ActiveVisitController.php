<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\QrCode;
use App\Services\AccessScope;
use App\Services\VisitListService;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tamu Aktif untuk Resepsionis, Admin, dan Super Admin (RSP-1, RSP-5 sampai RSP-8, ADM-1).
 */
class ActiveVisitController extends Controller
{
    public function __construct(
        private readonly AccessScope $scope,
        private readonly VisitListService $lists,
    ) {
    }

    /**
     * Halaman Tamu Aktif; baris dimuat dan diperbarui lewat polling JSON.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $locations = $user->role === UserRole::Receptionist
            ? collect()
            : Location::query()->forUser($user)->orderBy('name')->get(['id', 'name']);

        return view('admin.visits.active', [
            'filters' => $this->filters($request),
            'locations' => $locations,
            'isReceptionist' => $user->role === UserRole::Receptionist,
            'receptionistWithoutQr' => $user->role === UserRole::Receptionist
                && ! QrCode::query()->where('receptionist_id', $user->id)->exists(),
            'cancelReasons' => VisitService::CANCEL_REASONS,
        ]);
    }

    /**
     * Data JSON untuk polling: daftar, jumlah per label status, dan waktu server.
     */
    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $this->filters($request);
        $visits = $this->lists->activeVisits($user, $filters);

        return response()->json([
            'ok' => true,
            'data' => [
                'rows' => $visits->getCollection()->map(fn ($visit) => $this->lists->row($visit, $user))->values(),
                'counts' => $this->lists->statusCounts($user, $filters),
                'pagination' => [
                    'current_page' => $visits->currentPage(),
                    'last_page' => $visits->lastPage(),
                    'total' => $visits->total(),
                ],
                'server_time' => now()->format('H:i:s'),
            ],
        ]);
    }

    /**
     * Filter dari query string. Lokasi di luar cakupan diperlakukan sebagai akses ditolak (Bab 12.2).
     *
     * @return array{q: string|null, status: string|null, location_id: int|null}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', VisitListService::STATUS_FILTERS)],
            'location_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $locationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;

        if ($locationId !== null) {
            $this->scope->findOrDeny(Location::class, $locationId, $request->user());
        }

        return [
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? null,
            'location_id' => $locationId,
        ];
    }
}
