<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistoryRequest;
use App\Models\Location;
use App\Services\ExportService;
use App\Services\HistoryService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * History Kunjungan dan ekspor untuk Admin, Super Admin (semua lokasi), dan Resepsionis
 * (hanya kunjungan dari QR miliknya) (HIS-1 sampai HIS-6).
 */
class HistoryController extends Controller
{
    public function __construct(private readonly HistoryService $history)
    {
    }

    /**
     * Daftar History dengan filter di query string, 25 baris per halaman.
     */
    public function index(HistoryRequest $request): View
    {
        $user = $request->user();
        $filters = $request->filters();
        $this->history->ensureFiltersInScope($user, $filters);

        $visits = $this->history->query($user, $filters)
            ->with(['location:id,name', 'assignedDepartment:id,name', 'vulnerableGroupOption:code,name'])
            // Penanda tamu yang sudah mengunggah KTP lewat "Lengkapi profil" (TAM-18).
            ->withExists(['documents as has_ktp' => fn ($query) => $query->where('type', 'ktp')])
            ->paginate(25)
            ->withQueryString();

        return view('admin.history.index', array_merge($this->history->filterOptions($user), [
            'visits' => $visits,
            'filters' => $filters,
            'periods' => HistoryService::PERIODS,
            'statusLabels' => HistoryService::STATUS_LABELS,
        ]));
    }

    /**
     * Unduh ekspor PDF buku tamu mengikuti filter aktif dan cakupan (Bab 12.4, revisi HRD #3 dan #13).
     */
    public function export(HistoryRequest $request, ExportService $exports): Response
    {
        $user = $request->user();
        $filters = $request->filters();
        $this->history->ensureFiltersInScope($user, $filters);

        if (($filters['status'] ?? null) === 'dibatalkan') {
            throw ValidationException::withMessages(['status' => 'Kunjungan Dibatalkan tidak masuk buku tamu, jadi tidak dapat diekspor.']);
        }

        $locationName = empty($filters['location_id']) ? null : Location::query()->whereKey($filters['location_id'])->value('name');

        return $exports->download($this->history->query($user, $filters), $filters, $locationName, $user);
    }
}
