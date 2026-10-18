<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Services\GuestQrResolver;
use App\Services\GuestSessionService;
use App\Services\ReturningGuestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Riwayat kunjungan tamu sendiri beserta hasil dari PIC (TAM-17).
 */
class GuestHistoryController extends Controller
{
    /**
     * Menampilkan kunjungan dengan email terverifikasi di session, terbaru di atas.
     */
    public function show(
        string $token,
        GuestQrResolver $resolver,
        GuestSessionService $guestSession,
        ReturningGuestService $returning,
    ): View|RedirectResponse {
        $qr = $resolver->resolve($token);
        $email = $guestSession->verifiedEmail($qr);

        if ($email === null) {
            return redirect()->route('guest.show', $token)
                ->with('error', 'Verifikasi email kedaluwarsa. Silakan pilih akun Google lagi.');
        }

        return view('guest.history', [
            'qr' => $qr,
            'location' => $qr->location,
            'email' => $email,
            'visits' => $returning->history($email),
        ]);
    }
}
