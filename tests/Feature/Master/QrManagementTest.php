<?php

namespace Tests\Feature\Master;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use App\Models\GuestVisit;
use App\Models\QrCode;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji manajemen QR Code (QR-1 sampai QR-6).
 */
class QrManagementTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createUser(UserRole::SuperAdmin);
    }

    public function test_creates_qr_with_random_token_and_receptionist(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location);
        $receptionist = $this->createUser(UserRole::Receptionist);

        $this->actingAs($this->superAdmin)
            ->post('/admin/qr', [
                'location_id' => $location->id,
                'department_id' => $department->id,
                'label' => 'Pintu Timur',
                'receptionist_id' => $receptionist->id,
            ])
            ->assertSessionHasNoErrors();

        $qr = QrCode::firstOrFail();
        $this->assertSame($department->id, $qr->department_id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $qr->token);
        $this->assertSame(QrStatus::Active, $qr->status);
        $this->assertSame($receptionist->id, $qr->receptionist_id);
        $this->assertSame($this->superAdmin->id, $qr->created_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'qr.create', 'entity_id' => $qr->id]);
    }

    public function test_receptionist_must_be_active_receptionist(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location);
        $inactive = $this->createUser(UserRole::Receptionist, ['is_active' => false]);
        $admin = $this->createUser(UserRole::Admin);

        foreach ([$inactive, $admin] as $candidate) {
            $this->actingAs($this->superAdmin)
                ->post('/admin/qr', ['location_id' => $location->id, 'department_id' => $department->id, 'receptionist_id' => $candidate->id])
                ->assertSessionHasErrors('receptionist_id');
        }

        $this->assertDatabaseCount('qr_codes', 0);
    }

    public function test_url_is_built_from_app_url(): void
    {
        config(['app.url' => 'https://sitamu.contoh.go.id/']);
        $qr = $this->createQr($this->createLocation(), $this->createUser(UserRole::Receptionist));

        $this->assertSame('https://sitamu.contoh.go.id/tamu/'.$qr->token, app(QrService::class)->url($qr));
    }

    public function test_toggles_active_and_inactive(): void
    {
        $qr = $this->createQr($this->createLocation(), $this->createUser(UserRole::Receptionist));

        $this->actingAs($this->superAdmin)->post("/admin/qr/{$qr->id}/status");
        $this->assertSame(QrStatus::Inactive, $qr->refresh()->status);

        $this->actingAs($this->superAdmin)->post("/admin/qr/{$qr->id}/status");
        $this->assertSame(QrStatus::Active, $qr->refresh()->status);
    }

    public function test_revoked_qr_cannot_be_reactivated(): void
    {
        $qr = $this->createQr($this->createLocation(), $this->createUser(UserRole::Receptionist), QrStatus::Revoked);

        $this->actingAs($this->superAdmin)->post("/admin/qr/{$qr->id}/status")->assertSessionHasErrors('qr');

        $this->assertSame(QrStatus::Revoked, $qr->refresh()->status);
    }

    public function test_regenerate_creates_replacement_and_revokes_old_qr(): void
    {
        $location = $this->createLocation();
        $receptionist = $this->createUser(UserRole::Receptionist);
        $old = $this->createQr($location, $receptionist);
        $old->update(['label' => 'Pintu Timur']);

        $this->actingAs($this->superAdmin)->post("/admin/qr/{$old->id}/regenerate")->assertSessionHasNoErrors();

        $old->refresh();
        $replacement = QrCode::whereKeyNot($old->id)->firstOrFail();
        $this->assertSame(QrStatus::Revoked, $old->status);
        $this->assertNotNull($old->revoked_at);
        $this->assertSame(QrStatus::Active, $replacement->status);
        $this->assertNotSame($old->token, $replacement->token);
        $this->assertSame([$location->id, $receptionist->id, 'Pintu Timur'], [$replacement->location_id, $replacement->receptionist_id, $replacement->label]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'qr.regenerate', 'entity_id' => $replacement->id]);
    }

    public function test_changing_receptionist_moves_active_visits(): void
    {
        $location = $this->createLocation();
        $oldReceptionist = $this->createUser(UserRole::Receptionist);
        $newReceptionist = $this->createUser(UserRole::Receptionist);
        $qr = $this->createQr($location, $oldReceptionist);
        $visit = $this->createVisit($qr, $this->createCategory($location));

        $this->actingAs($this->superAdmin)->post("/admin/qr/{$qr->id}/resepsionis", ['receptionist_id' => $newReceptionist->id])
            ->assertSessionHasNoErrors();

        $this->assertTrue(GuestVisit::forUser($newReceptionist)->whereKey($visit->id)->exists());
        $this->assertFalse(GuestVisit::forUser($oldReceptionist)->whereKey($visit->id)->exists());
        $this->assertDatabaseHas('audit_logs', ['action' => 'qr.change_receptionist', 'entity_id' => $qr->id]);
    }

    public function test_downloads_png_and_svg(): void
    {
        $qr = $this->createQr($this->createLocation('Kantor Dinas'), $this->createUser(UserRole::Receptionist));

        $png = $this->actingAs($this->superAdmin)->get("/admin/qr/{$qr->id}/unduh?format=png")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'no-store, private');
        $size = getimagesizefromstring($png->getContent());
        $this->assertGreaterThanOrEqual(600, $size[0]);
        $this->assertStringContainsString('qr-kantor-dinas', $png->headers->get('Content-Disposition'));

        $this->actingAs($this->superAdmin)->get("/admin/qr/{$qr->id}/unduh?format=svg")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertDatabaseHas('audit_logs', ['action' => 'qr.download', 'entity_id' => $qr->id]);
    }

    public function test_print_page_contains_qr_and_instruction(): void
    {
        $qr = $this->createQr($this->createLocation('Kantor Dinas'), $this->createUser(UserRole::Receptionist));

        $this->actingAs($this->superAdmin)->get("/admin/qr/{$qr->id}/cetak")
            ->assertOk()
            ->assertSee('Pindai untuk mengisi buku tamu')
            ->assertSee('Kantor Dinas')
            ->assertSee('data:image/svg+xml;base64,', false);
    }

    public function test_admin_cannot_manage_qr(): void
    {
        $location = $this->createLocation();
        $admin = $this->createAdmin();
        $qr = $this->createQr($location, $this->createUser(UserRole::Receptionist));

        $this->actingAs($admin)->get("/admin/qr?lokasi={$location->id}")->assertForbidden();
        $this->actingAs($admin)->get("/admin/qr/{$qr->id}/unduh")->assertForbidden();
    }
}
