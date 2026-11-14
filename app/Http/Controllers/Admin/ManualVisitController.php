<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ManualVisitRequest;
use App\Models\GuestDocument;
use App\Models\QrCode;
use App\Models\User;
use App\Models\VulnerableGroup;
use App\Services\GuestDocumentService;
use App\Services\GuestQrResolver;
use App\Services\SignatureService;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Tambah Tamu Manual": resepsionis menginput kunjungan untuk tamu yang tidak bisa
 * login Google (RSP-11, revisi HRD #11). Kunjungan masuk Tamu Aktif resepsionis dan
 * ditugaskan seperti biasa.
 */
class ManualVisitController extends Controller
{
    public function __construct(private readonly GuestQrResolver $resolver)
    {
    }

    /**
     * Formulir kosong; pilihan pos hanya QR milik resepsionis yang dapat dipakai tamu.
     */
    public function create(Request $request): View
    {
        return view('admin.visits.manual', [
            'qrCodes' => $this->usableQrCodes($request->user()),
            'vulnerableGroups' => VulnerableGroup::query()->forGuestForm()->get(['code', 'name']),
        ]);
    }

    /**
     * Menyimpan kunjungan, tanda tangan, dan foto opsional dalam satu transaksi, sehingga
     * foto yang tidak valid membatalkan seluruh penyimpanan.
     */
    public function store(
        ManualVisitRequest $request,
        VisitService $visits,
        SignatureService $signatures,
        GuestDocumentService $documents,
    ): RedirectResponse {
        $user = $request->user();
        $qr = $this->usableQrCodes($user)->firstWhere('id', (int) $request->validated('qr_code_id'));

        if ($qr === null) {
            throw ValidationException::withMessages(['qr_code_id' => 'Pilih pos (QR) kunjungan.']);
        }

        $signaturePng = $signatures->normalize($request->validated('signature'));

        $visit = DB::transaction(function () use ($request, $visits, $documents, $qr, $user, $signaturePng) {
            $visit = $visits->recordManualVisit($qr, $request->visitData(), $user, $signaturePng, $request->ip());

            foreach (array_keys(GuestDocument::TYPES) as $type) {
                $dataUrl = $request->validated("{$type}_data");
                $file = $request->file("{$type}_photo");

                if (filled($dataUrl) || $file !== null) {
                    try {
                        $documents->store($qr, $visit->email, $type, $dataUrl, $file, $request->ip(), $user);
                    } catch (ValidationException $exception) {
                        throw ValidationException::withMessages([
                            "{$type}_photo" => GuestDocument::TYPES[$type].': '.$exception->validator->errors()->first(),
                        ]);
                    }
                }
            }

            return $visit;
        });

        return redirect()->route('admin.visits.active')
            ->with('success', "Kunjungan {$visit->name} berhasil ditambahkan. Silakan tugaskan seperti biasa.");
    }

    /**
     * QR aktif milik resepsionis yang lokasinya aktif dan punya Bidang/UPT aktif (TAM-6).
     *
     * @return Collection<int, QrCode>
     */
    private function usableQrCodes(User $user): Collection
    {
        return QrCode::query()
            ->where('receptionist_id', $user->id)
            ->where('status', QrStatus::Active)
            ->with('location')
            ->orderBy('label')
            ->get()
            ->filter(fn (QrCode $qr) => $this->resolver->isUsable($qr))
            ->values();
    }
}
