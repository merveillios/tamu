<?php

namespace App\Http\Controllers\Guest;

use App\Enums\VulnerableGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuestVisitRequest;
use App\Models\PurposeCategory;
use App\Services\GuestQrResolver;
use App\Services\GuestSessionService;
use App\Services\ReturningGuestService;
use App\Services\SignatureService;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Formulir buku tamu, penyimpanan kunjungan, dan halaman terima kasih (TAM-3 sampai TAM-16).
 */
class GuestFormController extends Controller
{
    private const EXPIRED_MESSAGE = 'Verifikasi email kedaluwarsa. Silakan pilih akun Google lagi.';

    public function __construct(
        private readonly GuestQrResolver $resolver,
        private readonly GuestSessionService $guestSession,
    ) {
    }

    /**
     * Formulir kosong untuk tamu baru, atau dengan data diri terkunci untuk tamu lama (TAM-16).
     */
    public function show(string $token, ReturningGuestService $returning): View|RedirectResponse
    {
        $qr = $this->resolver->resolve($token);
        $email = $this->guestSession->verifiedEmail($qr);

        if ($email === null) {
            return redirect()->route('guest.show', $token)->with('error', self::EXPIRED_MESSAGE);
        }

        return view('guest.form', [
            'qr' => $qr,
            'location' => $qr->location,
            'email' => $email,
            'profile' => $returning->latestProfile($email),
            'categories' => PurposeCategory::query()
                ->where('location_id', $qr->location_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'vulnerableGroups' => VulnerableGroup::cases(),
            'formToken' => $this->guestSession->newFormToken($qr),
        ]);
    }

    /**
     * Menyimpan kunjungan. Urutan pemeriksaan: honeypot, email di session, token sekali
     * pakai, lalu validasi; nomor urut baru diambil setelah semua lolos.
     */
    public function store(Request $request, string $token, SignatureService $signatures, VisitService $visits): View|RedirectResponse
    {
        $qr = $this->resolver->resolve($token);

        // TAM-8: honeypot terisi berarti bot; tampilkan sukses palsu tanpa menyimpan apa pun.
        if (filled($request->input('website'))) {
            return view('guest.thanks', ['qr' => $qr, 'location' => $qr->location, 'thanks' => null]);
        }

        $email = $this->guestSession->verifiedEmail($qr);

        if ($email === null) {
            return redirect()->route('guest.show', $token)->with('error', self::EXPIRED_MESSAGE);
        }

        if (! $this->guestSession->consumeFormToken($qr, $request->input('form_token'))) {
            return redirect()->route('guest.thanks', $token, 303);
        }

        $form = app(GuestVisitRequest::class);
        $data = $form->guestData();

        $visit = $visits->recordGuestVisit(
            $qr,
            PurposeCategory::findOrFail($data['purpose_category_id']),
            $data,
            $email,
            $this->guestSession->verifiedAt($qr) ?? now(),
            $signatures->normalize($data['signature']),
            $request->ip(),
        );

        return redirect()->route('guest.thanks', $token, 303)->with('guest_thanks', [
            'name' => $visit->name,
            'location' => $qr->location->name,
            'number' => $visit->daily_number,
            'time' => $visit->check_in_at->format('H:i'),
        ]);
    }

    /**
     * Halaman konfirmasi dari flash session; tanpa flash kembali ke awal (TAM-5).
     */
    public function thanks(string $token): View|RedirectResponse
    {
        $qr = $this->resolver->resolve($token);
        $thanks = session('guest_thanks');

        if (! is_array($thanks)) {
            return redirect()->route('guest.show', $token);
        }

        return view('guest.thanks', ['qr' => $qr, 'location' => $qr->location, 'thanks' => $thanks]);
    }
}
