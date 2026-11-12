<?php

namespace Tests\Feature\Guest;

use App\Enums\UserRole;
use App\Models\GuestDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji "Lengkapi profil": foto KTP dan selfie tamu (TAM-18, revisi HRD #4).
 */
class GuestDocumentTest extends TestCase
{
    use InteractsWithGuestPages;
    use RefreshDatabase;

    public function test_returning_guest_uploads_ktp_from_history_page(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $this->verifiedAs($qr, 'lama@contoh.test');

        $this->get("/tamu/{$qr->token}/riwayat")->assertOk()->assertSee('Lengkapi profil')->assertSee('Belum ada');

        $this->post("/tamu/{$qr->token}/profil/ktp", ['image_data' => $this->jpegDataUrl(1200, 760)])
            ->assertRedirect(route('guest.history', $qr->token))
            ->assertSessionHas('success', 'Foto KTP berhasil disimpan.');

        $document = GuestDocument::firstOrFail();
        $this->assertSame(['lama@contoh.test', 'ktp', 'image/jpeg', 1200, 760, $qr->id], [
            $document->email, $document->type, $document->mime_type, $document->width, $document->height, $document->qr_code_id,
        ]);
        $this->assertSame(hash('sha256', $document->image), $document->sha256);
        $this->assertDatabaseHas('audit_logs', ['action' => 'guest_document.upload', 'entity_id' => $document->id, 'user_id' => null]);

        $this->get("/tamu/{$qr->token}/riwayat")->assertSee('Tersimpan');
        $this->get("/tamu/{$qr->token}/profil/ktp")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get("/tamu/{$qr->token}/profil/selfie")->assertNotFound();
    }

    public function test_replacing_keeps_old_photo_and_serves_newest(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $this->verifiedAs($qr, 'lama@contoh.test');

        $this->post("/tamu/{$qr->token}/profil/selfie", ['image_data' => $this->jpegDataUrl(600, 800)]);
        $this->post("/tamu/{$qr->token}/profil/selfie", ['image_data' => $this->jpegDataUrl(900, 1200)]);

        $this->assertSame(2, GuestDocument::where('type', 'selfie')->count());
        $newest = GuestDocument::latest('id')->firstOrFail();
        $this->assertSame($newest->image, $this->get("/tamu/{$qr->token}/profil/selfie")->getContent());
    }

    public function test_large_upload_without_javascript_is_resized_and_reencoded(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $this->verifiedAs($qr, 'lama@contoh.test');

        $this->post("/tamu/{$qr->token}/profil/ktp", ['photo' => UploadedFile::fake()->image('ktp.png', 3200, 2000)])
            ->assertSessionHasNoErrors();

        $document = GuestDocument::firstOrFail();
        $this->assertSame([1600, 1000, 'image/jpeg'], [$document->width, $document->height, getimagesizefromstring($document->image)['mime']]);
    }

    public function test_invalid_or_tiny_photos_are_rejected(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $this->verifiedAs($qr, 'lama@contoh.test');

        $this->post("/tamu/{$qr->token}/profil/ktp", ['image_data' => 'data:image/jpeg;base64,'.base64_encode('bukan gambar')])
            ->assertSessionHasErrors('photo');
        $this->post("/tamu/{$qr->token}/profil/ktp", ['image_data' => $this->jpegDataUrl(200, 120)])
            ->assertSessionHasErrors(['photo' => 'Foto terlalu kecil. Ambil foto lebih dekat atau gunakan resolusi lebih besar.']);
        $this->post("/tamu/{$qr->token}/profil/ktp", [])->assertSessionHasErrors('photo');

        $this->assertSame(0, GuestDocument::count());
    }

    public function test_only_verified_returning_guest_can_upload(): void
    {
        [$qr] = $this->usableQr();

        $this->post("/tamu/{$qr->token}/profil/ktp", ['image_data' => $this->jpegDataUrl(800, 500)])
            ->assertRedirect(route('guest.show', $qr->token));

        $this->verifiedAs($qr, 'baru@contoh.test');
        $this->get("/tamu/{$qr->token}/riwayat")->assertOk()->assertDontSee('Lengkapi profil');
        $this->post("/tamu/{$qr->token}/profil/ktp", ['image_data' => $this->jpegDataUrl(800, 500)])->assertForbidden();

        $this->assertSame(0, GuestDocument::count());
    }

    public function test_guest_cannot_see_photo_of_another_email(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test']);
        $this->storeDocument('lama@contoh.test', 'ktp');

        $this->verifiedAs($qr, 'lain@contoh.test');
        $this->get("/tamu/{$qr->token}/profil/ktp")->assertNotFound();
    }

    public function test_staff_access_follows_signature_rules_and_is_audited(): void
    {
        [$qr, $category] = $this->usableQr();
        $visit = $this->createVisit($qr, $category, ['email' => 'lama@contoh.test', 'status' => 'completed']);
        $this->storeDocument('lama@contoh.test', 'ktp');
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get("/admin/kunjungan/{$visit->id}")->assertOk()
            ->assertSee(route('admin.visits.document', [$visit->id, 'ktp']), false)
            ->assertDontSee(route('admin.visits.document', [$visit->id, 'selfie']), false);
        $this->actingAs($admin)->get("/admin/kunjungan/{$visit->id}/foto/ktp")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertDatabaseHas('audit_logs', ['action' => 'guest_document.view', 'user_id' => $admin->id]);
        $this->actingAs($admin)->get('/admin/history')->assertSee('title="Tamu sudah mengunggah foto KTP; lihat di Detail"', false);

        $this->actingAs($qr->receptionist)->get("/admin/kunjungan/{$visit->id}/foto/ktp")->assertOk();

        $otherReceptionist = $this->createUser(UserRole::Receptionist);
        $this->createQr($this->createLocation(), $otherReceptionist);
        $this->actingAs($otherReceptionist)->get("/admin/kunjungan/{$visit->id}/foto/ktp")->assertNotFound();

        $pic = $this->createPic($this->createDepartment($qr->location));
        $this->actingAs($pic)->get("/admin/kunjungan/{$visit->id}/foto/ktp")->assertForbidden();
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

    /**
     * Menyimpan foto langsung ke database untuk tes sisi petugas.
     */
    private function storeDocument(string $email, string $type): GuestDocument
    {
        $jpeg = base64_decode(substr($this->jpegDataUrl(800, 500), strlen('data:image/jpeg;base64,')));

        return GuestDocument::create([
            'email' => $email, 'type' => $type, 'image' => $jpeg, 'mime_type' => 'image/jpeg',
            'width' => 800, 'height' => 500, 'size_bytes' => strlen($jpeg), 'sha256' => hash('sha256', $jpeg),
        ]);
    }
}
