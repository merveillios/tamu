<?php

namespace Tests\Feature\Guest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji verifikasi ID token Google dengan Http::fake() (TAM-11).
 */
class GoogleVerificationTest extends TestCase
{
    use InteractsWithGuestPages;
    use RefreshDatabase;

    private const CLIENT_ID = 'client-uji.apps.googleusercontent.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['sitamu.google_client_id' => self::CLIENT_ID]);
    }

    public function test_valid_token_stores_lowercase_email_and_redirects_new_guest_to_form(): void
    {
        [$qr] = $this->usableQr();
        $nonce = $this->openStepOne($qr);
        $this->fakeTokeninfo(['nonce' => $nonce, 'email' => 'Tamu@Contoh.Test']);

        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'token-uji'])
            ->assertOk()
            ->assertJson(['ok' => true, 'data' => ['redirect' => route('guest.form', $qr->token)]]);

        $this->assertSame('tamu@contoh.test', session("guest.{$qr->id}.email")['email']);
    }

    public function test_returning_guest_is_redirected_to_history(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $nonce = $this->openStepOne($qr);
        $this->fakeTokeninfo(['nonce' => $nonce, 'email' => 'lama@contoh.test']);

        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'token-uji'])
            ->assertJson(['data' => ['redirect' => route('guest.history', $qr->token)]]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidClaims(): array
    {
        return [
            'aud salah' => [['aud' => 'client-lain.apps.googleusercontent.com']],
            'iss salah' => [['iss' => 'https://contoh.test']],
            'kedaluwarsa' => [['exp' => (string) (time() - 60)]],
            'email belum diverifikasi' => [['email_verified' => 'false']],
            'nonce berbeda' => [['nonce' => 'nonce-palsu']],
        ];
    }

    /**
     * @dataProvider invalidClaims
     *
     * @param  array<string, mixed>  $override
     */
    public function test_invalid_claims_are_rejected(array $override): void
    {
        [$qr] = $this->usableQr();
        $nonce = $this->openStepOne($qr);
        $this->fakeTokeninfo(array_merge(['nonce' => $nonce], $override));

        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'token-uji'])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'error' => ['message' => 'Verifikasi akun Google gagal. Silakan coba lagi.']]);

        $this->assertNull(session("guest.{$qr->id}.email"));
    }

    public function test_unreachable_google_is_reported_as_failure(): void
    {
        [$qr] = $this->usableQr();
        $this->openStepOne($qr);
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'token-uji'])->assertStatus(422);
    }

    public function test_nonce_can_only_be_used_once(): void
    {
        [$qr] = $this->usableQr();
        $nonce = $this->openStepOne($qr);
        $this->fakeTokeninfo(['nonce' => $nonce]);

        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'token-uji'])->assertOk();
        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'token-uji'])->assertStatus(422);
    }

    public function test_switch_account_clears_verified_email(): void
    {
        [$qr] = $this->usableQr();

        $this->verifiedAs($qr, 'tamu@contoh.test')->post("/tamu/{$qr->token}/ganti-akun")
            ->assertRedirect(route('guest.show', $qr->token));

        $this->assertNull(session("guest.{$qr->id}.email"));
    }

    public function test_verification_is_rate_limited(): void
    {
        config(['sitamu.guest.google_verify_rate_limit_max' => 2]);
        [$qr] = $this->usableQr();
        Http::fake(['*' => Http::response([], 400)]);

        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'x']);
        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'x']);

        $this->postJson("/tamu/{$qr->token}/verifikasi-google", ['credential' => 'x'])->assertStatus(429);
    }

    /**
     * Membuka Langkah 1 agar nonce tersimpan di session, lalu mengembalikannya.
     */
    private function openStepOne($qr): string
    {
        $this->get("/tamu/{$qr->token}")->assertOk();

        return session("guest.{$qr->id}.nonce");
    }

    /**
     * Memalsukan respons endpoint tokeninfo Google.
     *
     * @param  array<string, mixed>  $override
     */
    private function fakeTokeninfo(array $override = []): void
    {
        Http::fake(['oauth2.googleapis.com/*' => Http::response(array_merge([
            'aud' => self::CLIENT_ID,
            'iss' => 'https://accounts.google.com',
            'exp' => (string) (time() + 600),
            'email' => 'tamu@contoh.test',
            'email_verified' => 'true',
            'nonce' => '',
        ], $override))]);
    }
}
