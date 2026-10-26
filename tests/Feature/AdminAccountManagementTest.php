<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji batas pengelolaan akun oleh Admin: hanya Resepsionis dan PIC (USR-6).
 */
class AdminAccountManagementTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
    }

    public function test_admin_creates_receptionist_and_pic(): void
    {
        $department = $this->createDepartment($this->createLocation());

        $this->actingAs($this->admin)
            ->post('/admin/pengguna/tambah', $this->payload(['email' => 'resepsionis@contoh.test']))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin)
            ->post('/admin/pengguna/tambah', $this->payload(['email' => 'pic@contoh.test', 'role' => 'pic', 'department_id' => $department->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(UserRole::Receptionist, User::where('email', 'resepsionis@contoh.test')->firstOrFail()->role);
        $this->assertSame(UserRole::Pic, User::where('email', 'pic@contoh.test')->firstOrFail()->role);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.create', 'user_id' => $this->admin->id]);
    }

    public function test_admin_cannot_create_admin_or_super_admin(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs($this->admin)
                ->post('/admin/pengguna/tambah', $this->payload(['role' => $role]))
                ->assertSessionHasErrors('role');
        }

        $this->assertDatabaseMissing('users', ['email' => 'baru@contoh.test']);
    }

    public function test_admin_cannot_promote_receptionist_to_admin(): void
    {
        $receptionist = $this->createUser(UserRole::Receptionist);

        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs($this->admin)
                ->post("/admin/pengguna/{$receptionist->id}/ubah", $this->payload(['role' => $role, 'email' => $receptionist->email, 'password' => null, 'password_confirmation' => null]))
                ->assertSessionHasErrors('role');
        }

        $this->assertSame(UserRole::Receptionist, $receptionist->refresh()->role);
    }

    public function test_admin_resets_password_deactivates_and_unlocks_receptionist(): void
    {
        $receptionist = $this->createUser(UserRole::Receptionist, ['failed_logins' => 5, 'locked_until' => now()->addMinutes(10)]);

        $this->actingAs($this->admin)
            ->post("/admin/pengguna/{$receptionist->id}/ubah", $this->payload([
                'email' => $receptionist->email,
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ]))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post("/admin/pengguna/{$receptionist->id}/buka-kunci")->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post("/admin/pengguna/{$receptionist->id}/status")->assertSessionHasNoErrors();

        $receptionist->refresh();
        $this->assertTrue(Hash::check('password-baru-123', $receptionist->password));
        $this->assertNull($receptionist->locked_until);
        $this->assertFalse($receptionist->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_reset', 'user_id' => $this->admin->id, 'entity_id' => $receptionist->id]);
    }

    /**
     * Admin tidak boleh mengubah akun Admin lain, Super Admin, atau dirinya sendiri lewat menu Pengguna,
     * termasuk lewat request manual dengan peran yang sebenarnya diizinkan.
     */
    public function test_admin_cannot_touch_admin_or_super_admin_accounts(): void
    {
        $superAdmin = $this->createUser(UserRole::SuperAdmin, ['failed_logins' => 5, 'locked_until' => now()->addMinutes(10)]);
        $otherAdmin = $this->createAdmin();

        foreach ([$superAdmin, $otherAdmin, $this->admin] as $target) {
            $originalHash = $target->password;

            $this->actingAs($this->admin)->get("/admin/pengguna/{$target->id}/ubah")->assertForbidden();
            $this->actingAs($this->admin)
                ->post("/admin/pengguna/{$target->id}/ubah", $this->payload([
                    'email' => $target->email,
                    'password' => 'diambil-alih-123',
                    'password_confirmation' => 'diambil-alih-123',
                ]))
                ->assertForbidden();
            $this->actingAs($this->admin)->post("/admin/pengguna/{$target->id}/status")->assertForbidden();
            $this->actingAs($this->admin)->post("/admin/pengguna/{$target->id}/buka-kunci")->assertForbidden();

            $target->refresh();
            $this->assertSame($originalHash, $target->password);
            $this->assertNotSame(UserRole::Receptionist, $target->role);
            $this->assertTrue($target->is_active);
            $this->assertDatabaseHas('audit_logs', ['action' => 'access.denied', 'user_id' => $this->admin->id, 'detail->model' => 'User', 'detail->id' => $target->id]);
        }

        $this->assertNotNull($superAdmin->locked_until);
    }

    public function test_index_hides_actions_for_accounts_admin_cannot_manage(): void
    {
        $superAdmin = $this->createUser(UserRole::SuperAdmin);
        $receptionist = $this->createUser(UserRole::Receptionist);

        $html = $this->actingAs($this->admin)->get('/admin/pengguna')
            ->assertOk()
            ->assertSee($superAdmin->name)
            ->assertSee('Dikelola Super Admin')
            ->getContent();

        $this->assertStringContainsString("/admin/pengguna/{$receptionist->id}/ubah", $html);
        $this->assertStringNotContainsString("/admin/pengguna/{$superAdmin->id}/ubah", $html);
        $this->assertStringNotContainsString("/admin/pengguna/{$superAdmin->id}/status", $html);
    }

    public function test_form_only_offers_receptionist_and_pic(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/pengguna/tambah')->assertOk()->getContent();

        $this->assertStringContainsString('value="receptionist"', $html);
        $this->assertStringContainsString('value="pic"', $html);
        $this->assertStringNotContainsString('value="admin"', $html);
        $this->assertStringNotContainsString('value="super_admin"', $html);
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
