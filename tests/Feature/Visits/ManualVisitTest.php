<?php

namespace Tests\Feature\Visits;

use App\Enums\UserRole;
use App\Models\GuestDocument;
use App\Models\GuestVisit;
use App\Models\VisitSignature;
use App\Models\VisitStatusLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji "Tambah Tamu Manual" oleh resepsionis untuk tamu tanpa login Google
 * (RSP-11, revisi HRD #11) dan pemisahannya dari data tamu Google.
 */
class ManualVisitTest extends TestCase
{
    use InteractsWithGuestPages;
    use RefreshDatabase;

    public function test_receptionist_adds_manual_visit_that_enters_active_list(): void
    {
        [$qr] = $this->usableQr();
        $receptionist = $qr->receptionist;

        $this->actingAs($receptionist)->get('/admin/tamu')->assertOk()->assertSee('Tambah Tamu Manual');
        $this->actingAs($receptionist)->get('/admin/tamu/manual')->assertOk()->assertSee('Email tamu');

        $this->actingAs($receptionist)->post('/admin/tamu/manual', $this->payload($qr->id))
            ->assertRedirect(route('admin.visits.active'))
            ->assertSessionHasNoErrors();

        $visit = GuestVisit::firstOrFail();
        $this->assertSame(['manual@contoh.test', 'waiting', $receptionist->id, $qr->id], [
            $visit->email, $visit->status->value, $visit->entered_by, $visit->qr_code_id,
        ]);
        $this->assertNull($visit->email_verified_at);
        $this->assertTrue($visit->isManual());
        $this->assertSame('6281234567890', $visit->phone);
        $this->assertTrue(VisitSignature::whereKey($visit->id)->exists());
        $this->assertDatabaseHas('visit_status_logs', ['visit_id' => $visit->id, 'to_status' => 'waiting', 'changed_by' => $receptionist->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.manual_create', 'entity_id' => $visit->id, 'user_id' => $receptionist->id]);

        $row = collect($this->actingAs($receptionist)->getJson('/admin/tamu/data')->json('data.rows'))->firstWhere('id', $visit->id);
        $this->assertTrue($row['manual']);
        $this->assertTrue($row['actions']['assign']);
    }

    public function test_validation_and_qr_ownership(): void
    {
        [$qr] = $this->usableQr();
        [$otherQr] = $this->usableQr();
        $receptionist = $qr->receptionist;

        $this->actingAs($receptionist)->post('/admin/tamu/manual', $this->payload($qr->id, ['email' => '']))
            ->assertSessionHasErrors(['email' => 'Email tamu wajib diisi dengan alamat yang valid.']);
        $this->actingAs($receptionist)->post('/admin/tamu/manual', $this->payload($qr->id, ['signature' => '']))
            ->assertSessionHasErrors('signature');
        $this->actingAs($receptionist)->post('/admin/tamu/manual', $this->payload($otherQr->id))
            ->assertSessionHasErrors(['qr_code_id' => 'Pilih pos (QR) kunjungan.']);

        $this->assertSame(0, GuestVisit::count());
    }

    public function test_optional_photos_are_stored_and_invalid_photo_cancels_everything(): void
    {
        [$qr] = $this->usableQr();
        $receptionist = $qr->receptionist;

        $this->actingAs($receptionist)->post('/admin/tamu/manual', $this->payload($qr->id, [
            'ktp_data' => 'data:image/jpeg;base64,'.base64_encode('bukan gambar'),
        ]))->assertSessionHasErrors('ktp_photo');
        $this->assertSame([0, 0], [GuestVisit::count(), GuestDocument::count()]);

        $this->actingAs($receptionist)->post('/admin/tamu/manual', $this->payload($qr->id, ['ktp_data' => $this->jpegDataUrl(1000, 630)]))
            ->assertSessionHasNoErrors();

        $document = GuestDocument::firstOrFail();
        $this->assertSame(['manual@contoh.test', 'ktp', $receptionist->id], [$document->email, $document->type, $document->uploaded_by]);
        $this->assertSame(1, GuestVisit::count());
    }

    public function test_google_login_with_same_email_shows_manual_visit_but_does_not_prefill(): void
    {
        [$qr] = $this->usableQr();
        $this->actingAs($qr->receptionist)->post('/admin/tamu/manual', $this->payload($qr->id, ['ktp_data' => $this->jpegDataUrl(1000, 630)]));
        auth()->logout();

        $this->verifiedAs($qr, 'manual@contoh.test');

        // Email sudah punya kunjungan, jadi diarahkan ke riwayat yang memuat kunjungan manual.
        $this->get("/tamu/{$qr->token}")->assertRedirect(route('guest.history', $qr->token));
        $this->get("/tamu/{$qr->token}/riwayat")->assertOk()
            ->assertSee('Didaftarkan oleh resepsionis')
            ->assertSee('Lengkapi profil')
            ->assertSee('Belum ada');
        // Foto KTP dari resepsionis tidak ditampilkan ke pemilik email.
        $this->get("/tamu/{$qr->token}/profil/ktp")->assertNotFound();

        // Formulir Google tidak diisi dari data manual dan tetap meminta tanda tangan.
        $this->get("/tamu/{$qr->token}/formulir")->assertOk()
            ->assertDontSee('Data diri diambil dari kunjungan terakhir Anda.')
            ->assertDontSee('Budi Manual')
            ->assertSee('data-signature-canvas', false);
    }

    public function test_only_receptionist_can_add_manual_visit(): void
    {
        [$qr] = $this->usableQr();

        $admin = $this->createAdmin();

        $this->actingAs($admin)->get('/admin/tamu/manual')->assertForbidden();
        $this->actingAs($admin)->get('/admin/tamu')->assertDontSee('Tambah Tamu Manual');
        $this->flushSession();
        $this->actingAs($this->createUser(UserRole::SuperAdmin))->post('/admin/tamu/manual', $this->payload($qr->id))->assertForbidden();
        $this->assertSame(0, VisitStatusLog::count());
    }

    /**
     * Isian formulir manual yang valid.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(int $qrId, array $overrides = []): array
    {
        return array_merge([
            'qr_code_id' => $qrId,
            'email' => 'Manual@Contoh.test',
            'purpose' => 'Konsultasi izin usaha',
            'vulnerable_group' => 'lansia',
            'name' => 'Budi Manual',
            'organization' => 'Pribadi',
            'phone' => '081234567890',
            'address' => 'Jl. Pahlawan No. 3, Surabaya',
            'signature' => $this->signatureDataUrl(),
            'consent' => '1',
        ], $overrides);
    }

    /**
     * Foto JPEG polos berukuran tertentu sebagai data URL.
     */
    private function jpegDataUrl(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 220, 240));
        ob_start();
        imagejpeg($image, null, 80);
        imagedestroy($image);

        return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
    }
}
