<?php

namespace Tests\Feature\Guest;

use App\Enums\VisitStatus;
use App\Models\GuestVisit;
use App\Models\PurposeCategory;
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
        [$qr, $category] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, $this->formToken($qr), ['email' => 'palsu@contoh.test']))
            ->assertStatus(303)
            ->assertRedirect(route('guest.thanks', $qr->token));

        $visit = GuestVisit::with('signature')->firstOrFail();
        $this->assertSame('tamu@contoh.test', $visit->email);
        $this->assertSame(VisitStatus::Waiting, $visit->status);
        $this->assertSame('6281234567890', $visit->phone);
        $this->assertSame($category->name, $visit->purpose_category_name);
        $this->assertSame($category->suggested_department_id, $visit->suggested_department_id);
        $this->assertSame(1, $visit->daily_number);
        $this->assertSame('image/png', getimagesizefromstring($visit->signature->image)['mime']);
        $this->assertNotNull($qr->refresh()->last_used_at);
        $this->assertDatabaseHas('visit_status_logs', ['visit_id' => $visit->id, 'from_status' => null, 'to_status' => 'waiting', 'changed_by' => null]);
    }

    public function test_thanks_page_shows_number_once_then_redirects(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, $this->formToken($qr)));

        $this->get("/tamu/{$qr->token}/terima-kasih")->assertOk()->assertSee('No. 1')->assertSee('Siti Aminah');
        $this->get("/tamu/{$qr->token}/terima-kasih")->assertRedirect(route('guest.show', $qr->token));
    }

    public function test_daily_number_increments_per_location_and_resets_next_day(): void
    {
        [$qrA, $categoryA] = $this->usableQr();
        [$qrB, $categoryB] = $this->usableQr();

        $this->submitAs($qrA, $categoryA, 'a1@contoh.test');
        $this->submitAs($qrA, $categoryA, 'a2@contoh.test');
        $this->submitAs($qrB, $categoryB, 'b1@contoh.test');

        $this->travel(1)->days();
        $this->submitAs($qrA, $categoryA, 'a3@contoh.test');

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
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, session("guest.{$qr->id}.form_token"), [
            'name' => 'Nama Palsu',
            'phone' => '089999999999',
        ]))->assertStatus(303);

        $latest = GuestVisit::latest('id')->firstOrFail();
        $this->assertSame(['Nama Lama', 'Instansi Lama', '6281111111111', 'Alamat Lama No. 1', 'lansia'], [
            $latest->name, $latest->organization, $latest->phone, $latest->address, $latest->vulnerable_group->value,
        ]);
    }

    public function test_used_form_token_does_not_save_twice(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');
        $payload = $this->guestPayload($category, $this->formToken($qr));

        $this->post("/tamu/{$qr->token}/formulir", $payload)->assertStatus(303);
        $this->post("/tamu/{$qr->token}/formulir", $payload)->assertRedirect(route('guest.thanks', $qr->token));

        $this->assertSame(1, GuestVisit::count());
    }

    public function test_honeypot_shows_fake_success_without_saving(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, $this->formToken($qr), ['website' => 'http://spam.test']))
            ->assertOk()
            ->assertSee('Terima kasih');

        $this->assertSame(0, GuestVisit::count());
    }

    public function test_invalid_input_is_rejected_and_signature_is_not_flashed(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, $this->formToken($qr), [
            'phone' => '12345',
            'consent' => null,
        ]))->assertSessionHasErrors(['phone', 'consent']);

        $this->assertArrayNotHasKey('signature', session()->getOldInput());
        $this->assertSame(0, GuestVisit::count());
        $this->assertDatabaseCount('visit_counters', 0);
    }

    public function test_blank_signature_is_rejected(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, $this->formToken($qr), [
            'signature' => $this->signatureDataUrl(withInk: false),
        ]))->assertSessionHasErrors(['signature' => 'Tanda tangan wajib dibubuhkan.']);
    }

    public function test_category_from_other_location_is_rejected(): void
    {
        [$qr] = $this->usableQr();
        [, $otherCategory] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($otherCategory, $this->formToken($qr)))
            ->assertSessionHasErrors('purpose_category_id');
    }

    public function test_submission_is_rate_limited_per_ip_and_qr(): void
    {
        config(['sitamu.guest.rate_limit_max' => 1]);
        [$qr, $category] = $this->usableQr();
        $this->verifiedAs($qr, 'tamu@contoh.test');

        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, $this->formToken($qr)))->assertStatus(303);
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, 'apa-saja'))->assertStatus(429);
    }

    /**
     * Mengirim formulir valid sebagai tamu dengan email tertentu.
     */
    private function submitAs($qr, PurposeCategory $category, string $email): void
    {
        $this->flushSession();
        $this->verifiedAs($qr, $email);
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload($category, $this->formToken($qr)))->assertStatus(303);
    }
}
