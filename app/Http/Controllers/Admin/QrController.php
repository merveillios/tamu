<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\QrRequest;
use App\Models\Department;
use App\Models\Location;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\GuestQrResolver;
use App\Services\QrService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Manajemen QR Code oleh Admin dan Super Admin (QR-1 sampai QR-6).
 */
class QrController extends Controller
{
    public function __construct(
        private readonly AccessScope $scope,
        private readonly QrService $qrService,
        private readonly GuestQrResolver $resolver,
    ) {
    }

    /**
     * Daftar QR semua lokasi (atau satu lokasi lewat parameter `lokasi`) beserta form buat QR.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $selected = $request->filled('lokasi')
            ? $this->scope->findOrDeny(Location::class, $request->integer('lokasi'), $user)
            : null;

        $qrCodes = QrCode::query()
            ->forUser($user)
            ->when($selected, fn ($query) => $query->where('location_id', $selected->id))
            ->with(['location:id,name,is_active', 'receptionist:id,name,is_active'])
            // QR yang sudah dicabut ditaruh paling bawah sebagai riwayat.
            ->orderByRaw('status = ?', [QrStatus::Revoked->value])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // Jumlah Bidang/UPT aktif per lokasi untuk peringatan di form buat QR dan status "Belum siap" di daftar.
        $departmentCounts = $this->activeCountsPerLocation(Department::query()->forUser($user));

        return view('admin.qr.index', [
            'locations' => Location::query()->forUser($user)->orderBy('name')->get(['id', 'name', 'is_active']),
            'selected' => $selected,
            'qrCodes' => $qrCodes,
            'blockers' => $qrCodes->getCollection()->mapWithKeys(fn (QrCode $qr) => [
                $qr->id => $qr->status === QrStatus::Active
                    ? $this->resolver->blockers($qr, $departmentCounts->get($qr->location_id, 0) > 0)
                    : [],
            ]),
            'departmentCounts' => $departmentCounts,
            'receptionists' => User::query()
                ->where('role', UserRole::Receptionist)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Membuat QR baru untuk lokasi terpilih (QR-2). Lokasi tanpa Bidang/UPT aktif tetap
     * boleh dibuatkan QR agar bisa dicetak lebih dulu, disertai catatan bahwa QR belum
     * dapat dipakai tamu (TAM-6).
     */
    public function store(QrRequest $request): RedirectResponse
    {
        $location = $this->scope->findOrDeny(Location::class, $request->validated('location_id'), $request->user());
        $receptionist = User::findOrFail($request->validated('receptionist_id'));

        $this->qrService->create($location, $receptionist, $request->validated('label'), $request->user());

        $message = "QR Code baru untuk {$location->name} berhasil dibuat.";

        if (! $location->departments()->where('is_active', true)->exists()) {
            $message .= ' QR belum dapat dipakai tamu sampai lokasi ini punya Bidang/UPT aktif.';
        }

        return redirect()->route('admin.qr.index', ['lokasi' => $location->id])->with('success', $message);
    }

    /**
     * Mengganti akun resepsionis pemilik QR (QR-6).
     */
    public function changeReceptionist(QrRequest $request, int $id): RedirectResponse
    {
        $qr = $this->scope->findOrDeny(QrCode::class, $id, $request->user());
        $receptionist = User::findOrFail($request->validated('receptionist_id'));

        $this->qrService->changeReceptionist($qr, $receptionist, $request->user());

        return back()->with('success', "QR kini dipegang {$receptionist->name}.");
    }

    /**
     * Mengaktifkan atau menonaktifkan QR (QR-3).
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        $qr = $this->scope->findOrDeny(QrCode::class, $id, $request->user());
        $this->qrService->setActive($qr, $qr->status !== QrStatus::Active, $request->user());

        return back()->with('success', $qr->status === QrStatus::Active ? 'QR diaktifkan.' : 'QR dinonaktifkan.');
    }

    /**
     * Mengganti QR dengan token baru dan mencabut QR lama (QR-4).
     */
    public function regenerate(Request $request, int $id): RedirectResponse
    {
        $qr = $this->scope->findOrDeny(QrCode::class, $id, $request->user());
        $this->qrService->regenerate($qr, $request->user());

        return back()->with('success', 'QR baru berhasil dibuat. QR lama yang sudah dicetak tidak berfungsi lagi.');
    }

    /**
     * Mengunduh gambar QR dalam format PNG atau SVG (QR-5).
     */
    public function download(Request $request, int $id): Response
    {
        $format = $request->validate(['format' => ['nullable', 'in:png,svg']])['format'] ?? 'png';
        $qr = $this->scope->findOrDeny(QrCode::class, $id, $request->user());

        $this->qrService->recordDownload($qr, $format, $request->user());

        $parts = [$qr->location->name, $qr->label ?: 'qr-'.$qr->id];
        $filename = 'qr-'.collect($parts)->filter()->map(fn (string $part) => Str::slug($part))->implode('-').'.'.$format;

        return response($this->qrService->image($qr, $format), 200, [
            'Content-Type' => $format === 'svg' ? 'image/svg+xml' : 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Halaman cetak QR ukuran A4 (QR-5).
     */
    public function print(Request $request, int $id): View
    {
        $qr = $this->scope->findOrDeny(QrCode::class, $id, $request->user());

        return view('admin.qr.print', [
            'qr' => $qr->load('location'),
            'imageUri' => $this->qrService->svgDataUri($qr),
        ]);
    }

    /**
     * Jumlah data aktif per lokasi, dengan kunci location_id.
     *
     * @return Collection<int, int>
     */
    private function activeCountsPerLocation(Builder $query): Collection
    {
        return $query->where('is_active', true)->pluck('location_id')->countBy();
    }
}
