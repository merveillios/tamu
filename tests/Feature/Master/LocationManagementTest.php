<?php

namespace Tests\Feature\Master;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji manajemen lokasi (LOK-1 sampai LOK-3).
 */
class LocationManagementTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createUser(UserRole::SuperAdmin);
    }

    public function test_creates_location_and_audits(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/admin/lokasi/tambah', ['name' => '  Kantor   Dinas  ', 'description' => 'Gedung utama'])
            ->assertRedirect(route('admin.locations.index'));

        $location = Location::where('name', 'Kantor Dinas')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'location.create', 'entity_id' => $location->id]);
    }

    public function test_name_must_be_unique_ignoring_case_and_including_deleted(): void
    {
        $this->createLocation('Kantor Dinas');
        $deleted = $this->createLocation('Gudang Lama');
        $deleted->delete();

        $this->actingAs($this->superAdmin)->post('/admin/lokasi/tambah', ['name' => 'KANTOR dinas'])
            ->assertSessionHasErrors(['name' => 'Nama lokasi sudah digunakan']);
        $this->actingAs($this->superAdmin)->post('/admin/lokasi/tambah', ['name' => 'gudang lama'])
            ->assertSessionHasErrors(['name' => 'Nama lokasi sudah digunakan']);
    }

    public function test_updates_location(): void
    {
        $location = $this->createLocation('Kantor Lama');

        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$location->id}/ubah", ['name' => 'Kantor Baru'])
            ->assertRedirect(route('admin.locations.index'));

        $this->assertSame('Kantor Baru', $location->refresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'location.update', 'entity_id' => $location->id]);
    }

    public function test_toggles_status(): void
    {
        $location = $this->createLocation();

        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$location->id}/status");
        $this->assertFalse($location->refresh()->is_active);

        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$location->id}/status");
        $this->assertTrue($location->refresh()->is_active);

        $this->assertDatabaseHas('audit_logs', ['action' => 'location.deactivate', 'entity_id' => $location->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'location.activate', 'entity_id' => $location->id]);
    }

    public function test_empty_location_can_be_soft_deleted(): void
    {
        $location = $this->createLocation();

        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$location->id}/hapus")->assertSessionHasNoErrors();

        $this->assertSoftDeleted('locations', ['id' => $location->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'location.delete', 'entity_id' => $location->id]);
    }

    public function test_location_with_department_cannot_be_deleted(): void
    {
        $location = $this->createLocation();
        $this->createDepartment($location);

        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$location->id}/hapus")->assertSessionHasErrors('location');

        $this->assertNotSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_location_with_qr_cannot_be_deleted(): void
    {
        $location = $this->createLocation();
        $this->createQr($location, $this->createUser(UserRole::Receptionist));

        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$location->id}/hapus")->assertSessionHasErrors('location');
    }

    public function test_index_hides_delete_button_for_location_with_data(): void
    {
        $empty = $this->createLocation('Lokasi Kosong');
        $used = $this->createLocation('Lokasi Terpakai');
        $this->createDepartment($used);

        $html = $this->actingAs($this->superAdmin)->get('/admin/lokasi')->assertOk()->getContent();

        $this->assertStringContainsString("/admin/lokasi/{$empty->id}/hapus", $html);
        $this->assertStringNotContainsString("/admin/lokasi/{$used->id}/hapus", $html);
    }

    public function test_admin_manages_locations_of_any_location(): void
    {
        $other = $this->createLocation('Lokasi Lain');
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get('/admin/lokasi')->assertOk()->assertSee('Lokasi Lain');
        $this->actingAs($admin)->post('/admin/lokasi/tambah', ['name' => 'Kantor UPT'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/admin/lokasi/{$other->id}/ubah", ['name' => 'Lokasi Lain Baru'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/admin/lokasi/{$other->id}/status");

        $this->assertFalse($other->refresh()->is_active);
        $this->assertSame('Lokasi Lain Baru', $other->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'location.create', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'location.deactivate', 'user_id' => $admin->id, 'entity_id' => $other->id]);
    }

    public function test_receptionist_and_pic_cannot_manage_locations(): void
    {
        $location = $this->createLocation();

        foreach ([$this->createUser(UserRole::Receptionist), $this->createPic($this->createDepartment($location))] as $user) {
            // Sesi terpisah per akun agar auth.session tidak memakai hash password akun sebelumnya.
            $this->flushSession();
            $this->actingAs($user)->get('/admin/lokasi')->assertForbidden();
            $this->actingAs($user)->post("/admin/lokasi/{$location->id}/status")->assertForbidden();
        }
    }
}
