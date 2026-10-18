<?php

namespace Tests\Feature\Master;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji manajemen departemen per lokasi (DEP-1 sampai DEP-3).
 */
class DepartmentManagementTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createUser(UserRole::SuperAdmin);
    }

    public function test_adds_department_to_location(): void
    {
        $location = $this->createLocation();

        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$location->id}/departemen", ['name' => 'Bidang Industri'])
            ->assertSessionHasNoErrors();

        $department = Department::where('name', 'Bidang Industri')->firstOrFail();
        $this->assertSame($location->id, $department->location_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.create', 'entity_id' => $department->id]);
    }

    public function test_name_is_unique_per_location_ignoring_case(): void
    {
        $locationA = $this->createLocation();
        $locationB = $this->createLocation();
        $this->createDepartment($locationA, 'Sekretariat');

        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$locationA->id}/departemen", ['name' => 'SEKRETARIAT'])
            ->assertSessionHasErrors('name');
        $this->actingAs($this->superAdmin)->post("/admin/lokasi/{$locationB->id}/departemen", ['name' => 'Sekretariat'])
            ->assertSessionHasNoErrors();
    }

    public function test_rename_keeps_location(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location, 'Bidang Lama');

        $this->actingAs($this->superAdmin)->post("/admin/departemen/{$department->id}/ubah", [
            'name' => 'Bidang Baru',
            'location_id' => $this->createLocation()->id,
        ])->assertSessionHasNoErrors();

        $department->refresh();
        $this->assertSame('Bidang Baru', $department->name);
        $this->assertSame($location->id, $department->location_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.update', 'entity_id' => $department->id]);
    }

    public function test_toggles_status(): void
    {
        $department = $this->createDepartment($this->createLocation());

        $this->actingAs($this->superAdmin)->post("/admin/departemen/{$department->id}/status");

        $this->assertFalse($department->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.deactivate', 'entity_id' => $department->id]);
    }

    public function test_index_shows_active_pic_and_staff_count(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location, 'Bidang Perdagangan');
        $this->createPic($department)->update(['name' => 'PIC Perdagangan']);
        $this->createStaffMember($department);

        $this->actingAs($this->superAdmin)->get("/admin/lokasi/{$location->id}/departemen")
            ->assertOk()
            ->assertSee('Bidang Perdagangan')
            ->assertSee('PIC Perdagangan');
    }

    public function test_admin_cannot_manage_departments(): void
    {
        $location = $this->createLocation();
        $admin = $this->createAdmin($location);

        $this->actingAs($admin)->get("/admin/lokasi/{$location->id}/departemen")->assertForbidden();
        $this->actingAs($admin)->post("/admin/lokasi/{$location->id}/departemen", ['name' => 'Bidang X'])->assertForbidden();
    }

    public function test_unknown_location_gives_404(): void
    {
        $this->actingAs($this->superAdmin)->get('/admin/lokasi/999999/departemen')->assertNotFound();
    }
}
