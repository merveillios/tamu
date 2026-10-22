<?php

namespace Tests\Feature\Guest;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji pintu masuk halaman tamu (TAM-1, TAM-6, TAM-11, TAM-12, TAM-15).
 */
class GuestEntryTest extends TestCase
{
    use InteractsWithGuestPages;
    use RefreshDatabase;

    private const INACTIVE = 'QR Code ini tidak aktif. Silakan hubungi petugas di lokasi.';

    public function test_unusable_qr_shows_uniform_404(): void
    {
        [$inactiveQr] = $this->usableQr();
        $inactiveQr->update(['status' => QrStatus::Inactive]);

        [$qrInactiveLocation] = $this->usableQr();
        $qrInactiveLocation->location->update(['is_active' => false]);

        $locationWithoutCategory = $this->createLocation();
        $qrWithoutCategory = $this->createQr($locationWithoutCategory, $this->createUser(UserRole::Receptionist));

        foreach (['tidak-valid', str_repeat('a', 64), $inactiveQr->token, $qrInactiveLocation->token, $qrWithoutCategory->token] as $token) {
            $this->get("/tamu/{$token}")->assertNotFound()->assertSee(self::INACTIVE);
        }
    }

    public function test_step_one_shows_google_button_with_nonce_and_csp_exception(): void
    {
        config(['sitamu.google_client_id' => 'client-uji.apps.googleusercontent.com']);
        [$qr] = $this->usableQr();

        $response = $this->get("/tamu/{$qr->token}")
            ->assertOk()
            ->assertSee('data-client-id="client-uji.apps.googleusercontent.com"', false)
            ->assertSee('Tidak bisa login Google?')
            ->assertSee($qr->location->name)
            ->assertHeader('Referrer-Policy', 'no-referrer');

        $this->assertNotEmpty(session("guest.{$qr->id}.nonce"));
        $this->assertStringContainsString('https://accounts.google.com', $response->headers->get('Content-Security-Policy'));
    }

    public function test_other_guest_pages_keep_strict_csp(): void
    {
        [$qr] = $this->usableQr();

        $csp = $this->get("/tamu/{$qr->token}/bantuan")->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('accounts.google.com', $csp);
    }

    public function test_verified_new_guest_goes_to_form_and_returning_guest_to_history(): void
    {
        [$qr, $category] = $this->usableQr();

        $this->verifiedAs($qr, 'baru@contoh.test')->get("/tamu/{$qr->token}")
            ->assertRedirect(route('guest.form', $qr->token));

        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $this->verifiedAs($qr, 'lama@contoh.test')->get("/tamu/{$qr->token}")
            ->assertRedirect(route('guest.history', $qr->token));
    }

    public function test_expired_verification_returns_to_step_one(): void
    {
        [$qr] = $this->usableQr();
        $expired = now()->subMinutes(config('sitamu.guest.email_verify_ttl_minutes') + 1)->timestamp;

        $this->verifiedAs($qr, 'tamu@contoh.test', $expired)->get("/tamu/{$qr->token}/formulir")
            ->assertRedirect(route('guest.show', $qr->token))
            ->assertSessionHas('error', 'Verifikasi email kedaluwarsa. Silakan pilih akun Google lagi.');
    }

    public function test_help_page_offers_retry_only(): void
    {
        [$qr] = $this->usableQr();

        $this->get("/tamu/{$qr->token}/bantuan")
            ->assertOk()
            ->assertSee('Coba buka halaman ini di Chrome atau Safari. Jika masih belum bisa, silakan hubungi petugas.')
            ->assertSee('Coba lagi')
            ->assertDontSee('name="purpose"', false);
    }
}
