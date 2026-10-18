<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\QrRequest;
use App\Models\Location;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\QrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Manajemen QR Code per lokasi oleh Super Admin (QR-1 sampai QR-6).
 */
class QrController extends Controller
{
    public function __construct(
        private readonly AccessScope $scope,
        private readonly QrService $qrService,
    ) {
    }

    /**
     * Daftar QR sebuah lokasi beserta form buat QR.
     */
    public function index(Request $request, int $locationId): View
    {
        $location = $this->scope->findOrDeny(Location::class, $locationId, $request->user());

        $qrCodes = QrCode::query()
            ->forUser($request->user())
            ->where('location_id', $location->id)
            ->with('receptionist:id,name,is_active')
            // QR yang sudah dicabut ditaruh paling bawah sebagai riwayat.
            ->orderByRaw('status = ?', [QrStatus::Revoked->value])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.qr.index', [
            'location' => $location,
            'qrCodes' => $qrCodes,
            'receptionists' => User::query()
                ->where('role', UserRole::Receptionist)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Membuat QR baru untuk lokasi (QR-2).
     */
    public function store(QrRequest $request, int $locationId): RedirectResponse
    {
        $location = $this->scope->findOrDeny(Location::class, $locationId, $request->user());
        $receptionist = User::findOrFail($request->validated('receptionist_id'));

        $this->qrService->create($location, $receptionist, $request->validated('label'), $request->user());

        return back()->with('success', 'QR Code baru berhasil dibuat.');
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

        $filename = 'qr-'.Str::slug($qr->location->name).'-'.Str::slug($qr->label ?: 'qr-'.$qr->id).'.'.$format;

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
}
