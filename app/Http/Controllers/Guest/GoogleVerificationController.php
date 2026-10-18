<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Services\GoogleTokenVerifier;
use App\Services\GuestQrResolver;
use App\Services\GuestSessionService;
use App\Services\ReturningGuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Verifikasi email tamu lewat akun Google dan tombol Ganti akun (TAM-11).
 */
class GoogleVerificationController extends Controller
{
    public function __construct(
        private readonly GuestQrResolver $resolver,
        private readonly GuestSessionService $guestSession,
    ) {
    }

    /**
     * Menerima ID token dari Google Identity Services lalu menyimpan email terverifikasi di session.
     */
    public function verify(
        Request $request,
        string $token,
        GoogleTokenVerifier $verifier,
        ReturningGuestService $returning,
    ): JsonResponse {
        $qr = $this->resolver->resolve($token);
        $credential = $request->input('credential');
        $nonce = $this->guestSession->pullNonce($qr);

        $email = is_string($credential) && strlen($credential) <= 4096
            ? $verifier->verify($credential, $nonce)
            : null;

        if ($email === null) {
            return response()->json([
                'ok' => false,
                'error' => ['code' => 'GOOGLE_VERIFY_FAILED', 'message' => 'Verifikasi akun Google gagal. Silakan coba lagi.'],
                // Nonce lama sudah terpakai; browser memakai nonce baru untuk percobaan berikutnya.
                'data' => ['nonce' => $this->guestSession->newNonce($qr)],
            ], 422);
        }

        $this->guestSession->rememberEmail($qr, $email);
        $next = $returning->isReturning($email) ? 'guest.history' : 'guest.form';

        return response()->json(['ok' => true, 'data' => ['redirect' => route($next, $token)]]);
    }

    /**
     * Menghapus hasil verifikasi dan kembali ke Langkah 1.
     */
    public function switchAccount(string $token): RedirectResponse
    {
        $this->guestSession->forgetEmail($this->resolver->resolve($token));

        return redirect()->route('guest.show', $token);
    }
}
