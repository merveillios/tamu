<?php

namespace Tests\Feature\Guest;

use App\Enums\VisitStatus;
use App\Models\GuestVisit;
use App\Models\VisitSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji formulir dan penyimpanan kunjungan tamu (TAM-3 sampai TAM-16).
 */
class GuestFormTest extends TestCase
{
    use InteractsWithGuestPages;
    use RefreshDatabase;

    public function test_form_requires_verified_email(): void
    {
        [$qr] = $this->usableQr();

        $this->get("/tamu/{$qr->token}/formulir")->assertRedirect(route('guest.show', $qr->token));
    }

    public function test_saves_visit_with_email_from_session_and_redirects_303(): void
    {
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr), ['email' => 'palsu@contoh.test']))
            ->assertStatus(303)
            ->assertRedirect(route('guest.thanks', $qr->token));

        $visit = GuestVisit::with('signature')->firstOrFail();
        $this->assertSame('tamu@contoh.test', $visit->email);
        $this->assertSame(VisitStatus::Waiting, $visit->status);
        $this->assertSame('6281234567890', $visit->phone);
        $this->assertSame('Konsultasi perizinan perdagangan', $visit->purpose);
        $this->assertNull($visit->purpose_category_id);
        $this->assertNull($visit->purpose_category_name);
        $this->assertNull($visit->suggested_department_id);
        $this->assertSame(1, $visit->daily_number);
        $this->assertSame('image/png', getimagesizefromstring($visit->signature->image)['mime']);
        $this->assertNotNull($qr->refresh()->last_used_at);
        $this->assertDatabaseHas('visit_status_logs', ['visit_id' => $visit->id, 'from_status' => null, 'to_status' => 'waiting', 'changed_by' => null]);
    }

    public function test_thanks_page_confirms_submission_once_without_number_then_redirects(): void
    {
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr)));

        $response = $this->get("/tamu/{$qr->token}/terima-kasih");
        $response->assertOk()
            ->assertSee('Kunjungan terkirim')
            ->assertDontSee('No. 1')
            ->assertSee('Siti Aminah')
            ->assertSee('guest-header-banner')
            ->assertSee(config('sitamu.institution_name'))
            ->assertSee($qr->location->name);
        $this->assertSame(1, substr_count($response->getContent(), 'assets/img/logo-jatim.png'));

        $this->get("/tamu/{$qr->token}/terima-kasih")->assertRedirect(route('guest.show', $qr->token));
    }

    public function test_daily_number_increments_per_location_and_resets_next_day(): void
    {
        [$qrA] = $this->usableQr();
        [$qrB] = $this->usableQr();

        $this->submitAs($qrA, 'a1@contoh.test');
        $this->submitAs($qrA, 'a2@contoh.test');
        $this->submitAs($qrB, 'b1@contoh.test');

        $this->travel(1)->days();
        $this->submitAs($qrA, 'a3@contoh.test');

        $numbers = GuestVisit::orderBy('id')->pluck('daily_number', 'email')->all();
        $this->assertSame(['a1@contoh.test' => 1, 'a2@contoh.test' => 2, 'b1@contoh.test' => 1, 'a3@contoh.test' => 1], $numbers);
    }

    public function test_returning_guest_profile_comes_from_database_not_input(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, [
            'email' => 'lama@contoh.test',
            'name' => 'Nama Lama',
            'organization' => 'Instansi Lama',
            'phone' => '6281111111111',
            'address' => 'Alamat Lama No. 1',
            'vulnerable_group' => 'lansia',
        ]);
        $this->verifiedAs($qr, 'lama@contoh.test');

        $this->get("/tamu/{$qr->token}/formulir")->assertOk()->assertSee('Data diri diambil dari kunjungan terakhir Anda.');
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload(session("guest.{$qr->id}.form_token"), [
            'name' => 'Nama Palsu',
            'phone' => '089999999999',
        ]))->assertStatus(303);

        $latest = GuestVisit::latest('id')->firstOrFail();
        $this->assertSame(['Nama Lama', 'Instansi Lama', '6281111111111', 'Alamat Lama No. 1', 'lansia'], [
            $latest->name, $latest->organization, $latest->phone, $latest->address, $latest->vulnerable_group->value,
        ]);
    }

    public function test_returning_guest_with_signature_does_not_sign_again(): void
    {
        [$qr, $category] = $this->usableQr();
        $previous = $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $previousPng = $this->pngFrom($this->signatureDataUrl());
        VisitSignature::create(['visit_id' => $previous->id, 'image' => $previousPng, 'size_bytes' => strlen($previousPng), 'sha256' => hash('sha256', $previousPng)]);
        $this->verifiedAs($qr, 'lama@contoh.test');

        $this->get("/tamu/{$qr->token}/formulir")
            ->assertOk()
            ->assertSee('Tanda tangan diambil dari kunjungan pertama Anda.')
            ->assertDontSee('data-signature-canvas', false);

        $payload = $this->guestPayload(session("guest.{$qr->id}.form_token"), [
            // Tanda tangan dari browser diabaikan; yang dipakai salinan dari kunjungan sebelumnya.
            'signature' => $this->signatureDataUrl(500, 200),
        ]);
        $this->post("/tamu/{$qr->token}/formulir", $payload)->assertStatus(303);

        $latest = GuestVisit::latest('id')->firstOrFail();
        $this->assertSame($previousPng, VisitSignature::findOrFail($latest->id)->image);
    }

    public function test_returning_guest_without_signature_must_still_sign(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $this->verifiedAs($qr, 'lama@contoh.test');

        $this->get("/tamu/{$qr->token}/formulir")->assertOk()->assertSee('data-signature-canvas', false);

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload(session("guest.{$qr->id}.form_token"), ['signature' => null]))
            ->assertSessionHasErrors(['signature' => 'Tanda tangan wajib dibubuhkan.']);
    }

    public function test_used_form_token_does_not_save_twice(): void
    {
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');
        $payload = $this->guestPayload($this->formToken($qr));

        $this->post("/tamu/{$qr->token}/formulir", $payload)->assertStatus(303);
        $this->post("/tamu/{$qr->token}/formulir", $payload)->assertRedirect(route('guest.thanks', $qr->token));

        $this->assertSame(1, GuestVisit::count());
    }

    public function test_honeypot_shows_fake_success_without_saving(): void
    {
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr), ['website' => 'http://spam.test']))
            ->assertOk()
            ->assertSee('Terima kasih');

        $this->assertSame(0, GuestVisit::count());
    }

    public function test_invalid_input_is_rejected_and_signature_is_not_flashed(): void
    {
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr), [
            'phone' => '12345',
            'consent' => null,
        ]))->assertSessionHasErrors(['phone', 'consent']);

        $this->assertArrayNotHasKey('signature', session()->getOldInput());
        $this->assertSame(0, GuestVisit::count());
        $this->assertDatabaseCount('visit_counters', 0);
    }

    public function test_blank_signature_is_rejected(): void
    {
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr), [
            'signature' => $this->signatureDataUrl(withInk: false),
        ]))->assertSessionHasErrors(['signature' => 'Tanda tangan wajib dibubuhkan.']);
    }

    public function test_form_has_no_category_and_always_shows_required_purpose(): void
    {
        [$qr, $category] = $this->usableQr();

        $this->verifiedAs($qr, 'tamu@contoh.test')
            ->get("/tamu/{$qr->token}/formulir")
            ->assertOk()
            ->assertDontSee('name="purpose_category_id"', false)
            ->assertDontSee($category->name)
            ->assertDontSee('data-purpose-detail', false)
            ->assertSee('Rincian keperluan')
            ->assertSee('name="purpose"', false);
    }

    public function test_purpose_is_required(): void
    {
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr), ['purpose' => '  ']))
            ->assertSessionHasErrors(['purpose' => 'Rincian keperluan wajib diisi (3–500 karakter).']);

        $this->assertSame(0, GuestVisit::count());
    }

    public function test_category_sent_by_browser_is_ignored(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr), ['purpose_category_id' => $category->id]))
            ->assertStatus(303);

        $this->assertNull(GuestVisit::firstOrFail()->purpose_category_id);
    }

    public function test_submission_is_rate_limited_per_ip_and_qr(): void
    {
        config(['sitamu.guest.rate_limit_max' => 1]);
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr)))->assertStatus(303);
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload('apa-saja'))->assertStatus(429);
    }

    public function test_form_renders_unified_banner_and_email_card_without_arrival_text(): void
    {
        [$qr] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $response = $this->get("/tamu/{$qr->token}/formulir");
        $response->assertOk();

        // 1. Kalimat jam datang otomatis dihapus
        $response->assertDontSee('Jam datang dicatat otomatis saat formulir dikirim.');

        // 2. Kartu email terverifikasi tetap ada dan utuh
        $response->assertSee('Email terverifikasi');
        $response->assertSee('tamu@contoh.test');
        $response->assertSee('Ganti akun');
        $response->assertSee(route('guest.switch', $qr->token));

        // 3 & 4. Banner biru dan logo instansi & badge lokasi menyatu
        $response->assertSee('guest-header-banner');
        $response->assertSee(config('sitamu.institution_name'));
        $response->assertSee($qr->location->name);
        $this->assertSame(1, substr_count($response->getContent(), 'assets/img/logo-jatim.png'));
    }

    /**
     * Mengirim formulir valid sebagai tamu dengan email tertentu.
     */
    private function pngFrom(string $dataUrl): string
    {
        return base64_decode(substr($dataUrl, strlen('data:image/png;base64,')));
    }

    private function submitAs($qr, string $email): void
    {
        $this->flushSession();
        $this->verifiedAs($qr, $email);
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($this->formToken($qr)))->assertStatus(303);
    }
}
