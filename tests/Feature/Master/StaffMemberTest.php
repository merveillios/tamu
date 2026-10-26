<?php

namespace Tests\Feature\Master;

use App\Enums\UserRole;
use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji daftar petugas departemen (PTG-1, PTG-2).
 */
class StaffMemberTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    public function test_admin_adds_staff_to_department_in_any_location(): void
    {
        $department = $this->createDepartment($this->createLocation());
        $admin = $this->createAdmin($this->createLocation());

        $this->actingAs($admin)->post("/admin/petugas/tambah?department_id={$department->id}", ['name' => 'Budi Santoso'])
            ->assertRedirect(route('admin.staff.index', ['department_id' => $department->id]));

        $member = StaffMember::firstOrFail();
        $this->assertSame($department->id, $member->department_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.create', 'entity_id' => $member->id]);
    }

    public function test_name_is_unique_per_department_ignoring_case(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location);
        $other = $this->createDepartment($location);
        $admin = $this->createAdmin();
        StaffMember::create(['department_id' => $department->id, 'name' => 'Budi Santoso']);

        $this->actingAs($admin)->post("/admin/petugas/tambah?department_id={$department->id}", ['name' => 'budi santoso'])
            ->assertSessionHasErrors(['name' => 'Nama petugas sudah ada di departemen ini.']);
        $this->actingAs($admin)->post("/admin/petugas/tambah?department_id={$other->id}", ['name' => 'Budi Santoso'])
            ->assertSessionHasNoErrors();
    }

    public function test_renames_and_toggles_staff(): void
    {
        $location = $this->createLocation();
        $member = $this->createStaffMember($this->createDepartment($location));
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post("/admin/petugas/{$member->id}/ubah", ['name' => 'Nama Baru'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/admin/petugas/{$member->id}/status");

        $member->refresh();
        $this->assertSame('Nama Baru', $member->name);
        $this->assertFalse($member->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.deactivate', 'entity_id' => $member->id]);
    }

    public function test_index_lists_staff_of_selected_department(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location);
        $other = $this->createDepartment($location);
        StaffMember::create(['department_id' => $department->id, 'name' => 'Petugas Satu']);
        StaffMember::create(['department_id' => $other->id, 'name' => 'Petugas Lain']);

        $this->actingAs($this->createUser(UserRole::SuperAdmin))->get("/admin/petugas?department_id={$department->id}")
            ->assertOk()
            ->assertSee('Petugas Satu')
            ->assertDontSee('Petugas Lain');
    }

    public function test_pic_cannot_manage_staff(): void
    {
        $department = $this->createDepartment($this->createLocation());

        $this->actingAs($this->createPic($department))->get('/admin/petugas')->assertForbidden();
    }
}
