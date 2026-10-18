<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrStatus;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LocationRequest;
use App\Models\Location;
use App\Services\AccessScope;
use App\Services\LocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manajemen lokasi oleh Super Admin (LOK-1 sampai LOK-3).
 */
class LocationController extends Controller
{
    public function __construct(private readonly AccessScope $scope)
    {
    }

    /**
     * Daftar lokasi beserta jumlah departemen, QR aktif, dan kunjungan aktif.
     */
    public function index(Request $request): View
    {
        $locations = Location::query()
            ->forUser($request->user())
            ->withCount([
                'departments',
                'qrCodes',
                'qrCodes as active_qr_codes_count' => fn ($query) => $query->where('status', QrStatus::Active),
                'guestVisits',
                'guestVisits as active_visits_count' => fn ($query) => $query->whereIn('status', [VisitStatus::Waiting, VisitStatus::Processing]),
            ])
            ->orderBy('name')
            ->paginate(25);

        return view('admin.locations.index', ['locations' => $locations]);
    }

    /**
     * Form lokasi baru.
     */
    public function create(): View
    {
        return view('admin.locations.form', ['location' => new Location()]);
    }

    /**
     * Menyimpan lokasi baru.
     */
    public function store(LocationRequest $request, LocationService $locations): RedirectResponse
    {
        $location = $locations->create($request->validated(), $request->user());

        return redirect()->route('admin.locations.index')->with('success', "Lokasi {$location->name} berhasil ditambahkan.");
    }

    /**
     * Form ubah lokasi.
     */
    public function edit(Request $request, int $id): View
    {
        return view('admin.locations.form', ['location' => $this->scope->findOrDeny(Location::class, $id, $request->user())]);
    }

    /**
     * Menyimpan perubahan lokasi.
     */
    public function update(LocationRequest $request, int $id, LocationService $locations): RedirectResponse
    {
        $location = $this->scope->findOrDeny(Location::class, $id, $request->user());
        $locations->update($location, $request->validated(), $request->user());

        return redirect()->route('admin.locations.index')->with('success', "Lokasi {$location->name} berhasil diperbarui.");
    }

    /**
     * Mengaktifkan atau menonaktifkan lokasi (LOK-2).
     */
    public function toggleStatus(Request $request, int $id, LocationService $locations): RedirectResponse
    {
        $location = $this->scope->findOrDeny(Location::class, $id, $request->user());
        $locations->setActive($location, ! $location->is_active, $request->user());

        $message = $location->is_active ? "Lokasi {$location->name} diaktifkan." : "Lokasi {$location->name} dinonaktifkan.";

        return back()->with('success', $message);
    }

    /**
     * Menghapus lokasi yang belum memiliki data (LOK-3).
     */
    public function destroy(Request $request, int $id, LocationService $locations): RedirectResponse
    {
        $location = $this->scope->findOrDeny(Location::class, $id, $request->user());
        $locations->delete($location, $request->user());

        return redirect()->route('admin.locations.index')->with('success', "Lokasi {$location->name} dihapus.");
    }
}
