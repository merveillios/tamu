<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SecurityHeaders;
use App\Services\GuestQrResolver;
use App\Services\GuestSessionService;
use App\Services\ReturningGuestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pintu masuk halaman tamu dari pemindaian QR (TAM-1, TAM-11, TAM-12, TAM-15).
 */
class GuestEntryController extends Controller
{
    public function __construct(
        private readonly GuestQrResolver $resolver,
        private readonly GuestSessionService $guestSession,
    ) {
    }

    /**
     * Langkah 1 (verifikasi Google) bila email belum terverifikasi; jika sudah,
     * arahkan ke formulir (tamu baru) atau riwayat (tamu lama).
     */
    public function show(Request $request, string $token, ReturningGuestService $returning): View|RedirectResponse
    {
        $qr = $this->resolver->resolve($token);
        $email = $this->guestSession->verifiedEmail($qr);

        if ($email !== null) {
            return redirect()->route($returning->isReturning($email) ? 'guest.history' : 'guest.form', $token);
        }

        $request->attributes->set(SecurityHeaders::ALLOW_GOOGLE_SIGN_IN, true);

        return view('guest.verify', [
            'qr' => $qr,
            'location' => $qr->location,
            'nonce' => $this->guestSession->newNonce($qr),
            'clientId' => config('sitamu.google_client_id'),
        ]);
    }

    /**
     * Halaman bantuan bagi tamu yang tidak bisa login Google (TAM-12).
     */
    public function help(string $token): View
    {
        $qr = $this->resolver->resolve($token);

        return view('guest.help', ['qr' => $qr, 'location' => $qr->location]);
    }
}
