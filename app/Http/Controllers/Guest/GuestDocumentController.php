<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\GuestDocument;
use App\Services\GuestDocumentService;
use App\Services\GuestQrResolver;
use App\Services\GuestSessionService;
use App\Services\ReturningGuestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * "Lengkapi profil" di riwayat tamu: unggah dan lihat foto KTP serta selfie milik
 * email terverifikasi sendiri (TAM-18, revisi HRD #4). Foto bisa diganti, tidak bisa dihapus.
 */
class GuestDocumentController extends Controller
{
    public function __construct(
        private readonly GuestQrResolver $resolver,
        private readonly GuestSessionService $guestSession,
        private readonly GuestDocumentService $documents,
    ) {
    }

    /**
     * Menyimpan foto baru. Hanya tamu yang sudah pernah berkunjung, karena profil
     * dilengkapi setelah kunjungan tercatat.
     */
    public function store(Request $request, string $token, string $type, ReturningGuestService $returning): RedirectResponse
    {
        $qr = $this->resolver->resolve($token);
        $email = $this->guestSession->verifiedEmail($qr);

        if ($email === null) {
            return redirect()->route('guest.show', $token)
                ->with('error', 'Verifikasi email kedaluwarsa. Silakan pilih akun Google lagi.');
        }

        abort_unless($returning->isReturning($email), 403);

        $request->validate([
            // Data URL hasil kompresi browser (sekitar 1600 px); batas longgar untuk base64.
            'image_data' => ['nullable', 'string', 'max:4000000'],
            // Cadangan bila JavaScript tidak berjalan: file asli dari kamera atau galeri.
            'photo' => ['nullable', 'required_without:image_data', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'image_data.*' => 'Foto terlalu besar. Coba foto lain.',
            'photo.required_without' => 'Pilih foto terlebih dahulu.',
            'photo.*' => 'Foto gagal diunggah. Gunakan foto JPG, PNG, atau WebP maksimal 10 MB.',
        ]);

        $this->documents->store($qr, $email, $type, $request->input('image_data'), $request->file('photo'), $request->ip());

        return redirect()->route('guest.history', $token)
            ->with('success', GuestDocument::TYPES[$type].' berhasil disimpan.');
    }

    /**
     * Foto terbaru milik tamu sendiri untuk pratinjau di halaman riwayat.
     */
    public function show(string $token, string $type): Response
    {
        $qr = $this->resolver->resolve($token);
        $email = $this->guestSession->verifiedEmail($qr) ?? abort(404);
        $document = $this->documents->latestImage($email, $type) ?? abort(404);

        return response($document->image, 200, [
            'Content-Type' => $document->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
