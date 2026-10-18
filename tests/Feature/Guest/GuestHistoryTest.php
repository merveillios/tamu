<?php

namespace Tests\Feature\Guest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji riwayat kunjungan tamu (TAM-17).
 */
class GuestHistoryTest extends TestCase
{
    use InteractsWithGuestPages;
    use RefreshDatabase;

    public function test_history_only_shows_visits_of_verified_email(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'saya@contoh.test', 'purpose' => 'Keperluan milik saya']);
        $this->createVisit($qr, $category, ['email' => 'orang-lain@contoh.test', 'purpose' => 'Keperluan orang lain']);

        $this->verifiedAs($qr, 'saya@contoh.test')->get("/tamu/{$qr->token}/riwayat")
            ->assertOk()
            ->assertSee('Keperluan milik saya')
            ->assertDontSee('Keperluan orang lain')
            ->assertSee('Isi formulir lagi');
    }

    public function test_history_includes_visits_from_other_locations_with_same_email(): void
    {
        [$qrA, $categoryA] = $this->usableQr();
        [$qrB, $categoryB] = $this->usableQr();
        $this->createVisit($qrB, $categoryB, ['email' => 'saya@contoh.test', 'purpose' => 'Kunjungan di lokasi B']);

        $this->verifiedAs($qrA, 'saya@contoh.test')->get("/tamu/{$qrA->token}/riwayat")->assertSee('Kunjungan di lokasi B');
    }

    public function test_result_is_shown_only_for_completed_visits_and_private_data_is_hidden(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, [
            'email' => 'saya@contoh.test',
            'status' => 'completed',
            'result' => 'Izin sudah diproses',
            'phone' => '6281234500000',
            'address' => 'Alamat Rahasia 99',
        ]);
        $this->createVisit($qr, $category, [
            'email' => 'saya@contoh.test',
            'status' => 'cancelled',
            'result' => 'Catatan belum final',
            'cancel_reason' => 'Data ganda',
        ]);

        $this->verifiedAs($qr, 'saya@contoh.test')->get("/tamu/{$qr->token}/riwayat")
            ->assertSee('Izin sudah diproses')
            ->assertDontSee('Catatan belum final')
            ->assertDontSee('Data ganda')
            ->assertDontSee('081234500000')
            ->assertDontSee('Alamat Rahasia 99');
    }

    public function test_history_requires_verified_email(): void
    {
        [$qr] = $this->usableQr();

        $this->get("/tamu/{$qr->token}/riwayat")->assertRedirect(route('guest.show', $qr->token));
    }
}
