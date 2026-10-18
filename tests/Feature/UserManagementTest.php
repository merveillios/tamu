<?php

namespace Tests\Feature;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji manajemen akun staf oleh Super Admin (USR-1 sampai USR-5, AUTH-4).
 */
class UserManagementTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createUser(UserRole::SuperAdmin);
    }

    public function test_creates_pic_with_department(): void
    {
        $department = $this->createDepartment($this->createLocation());

        $this->actingAs($this->superAdmin)
            ->post('/admin/pengguna/tambah', $this->payload(['role' => 'pic', 'department_id' => $department->id, 'email' => 'PIC@Contoh.test']))
            ->assertRedirect(route('admin.users.index'));

        $pic = User::where('email', 'pic@contoh.test')->firstOrFail();
        $this->assertSame(UserRole::Pic, $pic->role);
        $this->assertSame($department->id, $pic->department_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.create', 'entity_id' => $pic->id, 'user_id' => $this->superAdmin->id]);
    }

    public function test_creates_admin_with_locations_and_records_assigner(): void
    {
        $location = $this->createLocation();

        $this->actingAs($this->superAdmin)
            ->post('/admin/pengguna/tambah', $this->payload(['role' => 'admin', 'location_ids' => [$location->id]]))
            ->assertRedirect(route('admin.users.index'));

        $admin = User::where('email', 'baru@contoh.test')->firstOrFail();
        $this->assertDatabaseHas('user_locations', ['user_id' => $admin->id, 'location_id' => $location->id, 'assigned_by' => $this->superAdmin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.assign_locations', 'entity_id' => $admin->id]);
    }

    public function test_department_and_locations_are_ignored_for_receptionist(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location);

        $this->actingAs($this->superAdmin)->post('/admin/pengguna/tambah', $this->payload([
            'role' => 'receptionist',
            'department_id' => $department->id,
            'location_ids' => [$location->id],
        ]));

        $receptionist = User::where('email', 'baru@contoh.test')->firstOrFail();
        $this->assertNull($receptionist->department_id);
        $this->assertSame(0, $receptionist->locations()->count());
    }

    public function test_pic_requires_department(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/admin/pengguna/tambah', $this->payload(['role' => 'pic']))
            ->assertSessionHasErrors('department_id');
    }

    public function test_second_active_pic_for_same_department_is_rejected(): void
    {
        $department = $this->createDepartment($this->createLocation());
        $this->createPic($department);

        $this->actingAs($this->superAdmin)
            ->post('/admin/pengguna/tambah', $this->payload(['role' => 'pic', 'department_id' => $department->id]))
            ->assertSessionHasErrors(['department_id' => 'Departemen ini sudah memiliki akun PIC aktif.']);

        $this->assertDatabaseMissing('users', ['email' => 'baru@contoh.test']);
    }

    public function test_reactivating_pic_is_rejected_when_department_has_active_pic(): void
    {
        $department = $this->createDepartment($this->createLocation());
        $this->createPic($department);
        $inactivePic = $this->createUser(UserRole::Pic, ['department_id' => $department->id, 'is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->post("/admin/pengguna/{$inactivePic->id}/status")
            ->assertSessionHasErrors('department_id');

        $this->assertFalse($inactivePic->refresh()->is_active);
    }

    public function test_super_admin_cannot_deactivate_or_downgrade_self(): void
    {
        $this->actingAs($this->superAdmin)
            ->post("/admin/pengguna/{$this->superAdmin->id}/status")
            ->assertSessionHasErrors('role');

        $this->actingAs($this->superAdmin)
            ->post("/admin/pengguna/{$this->superAdmin->id}/ubah", $this->payload(['role' => 'admin', 'email' => $this->superAdmin->email, 'password' => null, 'password_confirmation' => null]))
            ->assertSessionHasErrors('role');

        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->is_active);
        $this->assertSame(UserRole::SuperAdmin, $this->superAdmin->role);
    }

    public function test_last_active_super_admin_cannot_be_removed(): void
    {
        $otherActor = $this->createUser(UserRole::Admin);

        $this->expectException(ValidationException::class);

        app(UserAccountService::class)->setActive($this->superAdmin, false, $otherActor);
    }

    public function test_receptionist_holding_qr_cannot_be_deactivated_or_change_role(): void
    {
        $receptionist = $this->createUser(UserRole::Receptionist);
        $this->createQr($this->createLocation(), $receptionist, QrStatus::Inactive);

        $this->actingAs($this->superAdmin)
            ->post("/admin/pengguna/{$receptionist->id}/status")
            ->assertSessionHasErrors(['role' => 'Pindahkan QR ke akun resepsionis lain terlebih dahulu.']);

        $this->actingAs($this->superAdmin)
            ->post("/admin/pengguna/{$receptionist->id}/ubah", $this->payload(['role' => 'admin', 'email' => $receptionist->email, 'password' => null, 'password_confirmation' => null]))
            ->assertSessionHasErrors('role');

        $this->assertTrue($receptionist->refresh()->is_active);
    }

    public function test_receptionist_with_only_revoked_qr_can_be_deactivated(): void
    {
        $receptionist = $this->createUser(UserRole::Receptionist);
        $this->createQr($this->createLocation(), $receptionist, QrStatus::Revoked);

        $this->actingAs($this->superAdmin)->post("/admin/pengguna/{$receptionist->id}/status")->assertSessionHasNoErrors();

        $this->assertFalse($receptionist->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivate', 'entity_id' => $receptionist->id]);
    }

    public function test_password_reset_by_super_admin_is_audited(): void
    {
        $admin = $this->createUser(UserRole::Admin);

        $this->actingAs($this->superAdmin)->post("/admin/pengguna/{$admin->id}/ubah", $this->payload([
            'role' => 'admin',
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => 'password-reset-123',
            'password_confirmation' => 'password-reset-123',
        ]))->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('password-reset-123', $admin->refresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_reset', 'entity_id' => $admin->id]);
    }

    public function test_unlock_resets_lock_and_is_audited(): void
    {
        $user = $this->createUser(UserRole::Pic, ['failed_logins' => 3, 'locked_until' => now()->addMinutes(10)]);

        $this->actingAs($this->superAdmin)->post("/admin/pengguna/{$user->id}/buka-kunci")->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame(0, $user->failed_logins);
        $this->assertNull($user->locked_until);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.unlock', 'entity_id' => $user->id]);
    }

    public function test_index_filters_by_search_role_and_lock_status(): void
    {
        $this->createUser(UserRole::Receptionist, ['name' => 'Resepsionis Pintu Timur']);
        $this->createUser(UserRole::Pic, ['name' => 'PIC Terkunci', 'locked_until' => now()->addMinutes(5)]);

        $this->actingAs($this->superAdmin)->get('/admin/pengguna?q=Pintu')
            ->assertSee('Resepsionis Pintu Timur')->assertDontSee('PIC Terkunci');
        $this->actingAs($this->superAdmin)->get('/admin/pengguna?role=pic')
            ->assertSee('PIC Terkunci')->assertDontSee('Resepsionis Pintu Timur');
        $this->actingAs($this->superAdmin)->get('/admin/pengguna?status=terkunci')
            ->assertSee('PIC Terkunci')->assertSee('Buka kunci')->assertDontSee('Resepsionis Pintu Timur');
    }

    /**
     * Data form akun yang valid, dapat ditimpa per tes.
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Akun Baru',
            'email' => 'baru@contoh.test',
            'role' => 'receptionist',
            'password' => 'password-awal-123',
            'password_confirmation' => 'password-awal-123',
        ], $overrides);
    }
}
