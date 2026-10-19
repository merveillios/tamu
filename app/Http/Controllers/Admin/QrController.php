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
use App\Services\QrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Manajemen QR Code oleh Super Admin (QR-1 sampai QR-7).
 */
class QrController extends Controller
{
    public function __construct(
        private readonly AccessScope $scope,
        private readonly QrService $qrService,
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
            ->with(['location:id,name', 'department:id,name,is_active', 'receptionist:id,name,is_active'])
            // QR yang sudah dicabut ditaruh paling bawah sebagai riwayat.
            ->orderByRaw('status = ?', [QrStatus::Revoked->value])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.qr.index', [
            'locations' => Location::query()->forUser($user)->orderBy('name')->get(['id', 'name', 'is_active']),
            'selected' => $selected,
            'qrCodes' => $qrCodes,
            'departments' => Department::query()
                ->forUser($user)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'location_id', 'name']),
            'receptionists' => User::query()
                ->where('role', UserRole::Receptionist)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Membuat QR baru untuk lokasi dan departemen terpilih (QR-2).
     */
    public function store(QrRequest $request): RedirectResponse
    {
        $location = $this->scope->findOrDeny(Location::class, $request->validated('location_id'), $request->user());
        $department = Department::findOrFail($request->validated('department_id'));
        $receptionist = User::findOrFail($request->validated('receptionist_id'));

        $this->qrService->create($location, $department, $receptionist, $request->validated('label'), $request->user());

        return redirect()->route('admin.qr.index', ['lokasi' => $location->id])
            ->with('success', "QR Code baru untuk {$department->name} berhasil dibuat.");
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
     * Mengganti departemen tujuan QR; hanya departemen aktif di lokasi QR yang dapat dipilih (QR-7).
     */
    public function changeDepartment(Request $request, int $id): RedirectResponse
    {
        $qr = $this->scope->findOrDeny(QrCode::class, $id, $request->user());

        $departmentId = $request->validate([
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('location_id', $qr->location_id)->where('is_active', true),
            ],
        ], [
            'department_id.*' => 'Pilih departemen aktif di lokasi QR ini.',
        ])['department_id'];

        $department = Department::findOrFail($departmentId);
        $this->qrService->changeDepartment($qr, $department, $request->user());

        return back()->with('success', "QR kini diarahkan ke {$department->name}.");
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

        $parts = [$qr->location->name, $qr->department?->name, $qr->label ?: 'qr-'.$qr->id];
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
            'qr' => $qr->load('location', 'department'),
            'imageUri' => $this->qrService->svgDataUri($qr),
        ]);
    }
}
