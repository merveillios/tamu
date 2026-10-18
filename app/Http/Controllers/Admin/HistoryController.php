<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistoryRequest;
use App\Models\Location;
use App\Services\ExportService;
use App\Services\HistoryService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * History Kunjungan dan ekspor untuk Admin dan Super Admin (HIS-1 sampai HIS-5).
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
            ->with(['location:id,name', 'assignedDepartment:id,name'])
            ->paginate(25)
            ->withQueryString();

        return view('admin.history.index', array_merge($this->history->filterOptions($user), [
            'visits' => $visits,
            'filters' => $filters,
            'periods' => HistoryService::PERIODS,
        ]));
    }

    /**
     * Unduh ekspor CSV, Excel, atau PDF mengikuti filter aktif dan cakupan (Bab 12.4).
     */
    public function export(HistoryRequest $request, ExportService $exports): Response
    {
        $user = $request->user();
        $filters = $request->filters();
        $this->history->ensureFiltersInScope($user, $filters);

        $locationName = empty($filters['location_id']) ? null : Location::query()->whereKey($filters['location_id'])->value('name');

        return $exports->download(
            $this->history->query($user, $filters),
            $request->validated('format') ?? 'csv',
            $filters,
            $locationName,
            $user,
        );
    }
}
