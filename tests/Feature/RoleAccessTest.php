<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan setiap peran mendapat 403 pada route peran lain (DoD M1, Bab 10.9).
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Route GET staf beserta peran yang berhak membukanya.
     *
     * @return array<string, array{0: string, 1: array<int, UserRole>}>
     */
    public static function staffRoutes(): array
    {
        $all = UserRole::cases();

        return [
            'ganti password' => ['/admin/password', $all],
            'tamu aktif' => ['/admin/tamu', [UserRole::SuperAdmin, UserRole::Admin, UserRole::Receptionist]],
            'penugasan' => ['/admin/penugasan', [UserRole::Pic]],
            'history' => ['/admin/history', [UserRole::SuperAdmin, UserRole::Admin]],
            'daftar pengguna' => ['/admin/pengguna', [UserRole::SuperAdmin, UserRole::Admin]],
            'tambah pengguna' => ['/admin/pengguna/tambah', [UserRole::SuperAdmin, UserRole::Admin]],
            'audit log' => ['/admin/audit-log', [UserRole::SuperAdmin, UserRole::Admin]],
            'lokasi' => ['/admin/lokasi', [UserRole::SuperAdmin, UserRole::Admin]],
            'tambah lokasi' => ['/admin/lokasi/tambah', [UserRole::SuperAdmin, UserRole::Admin]],
            'qr code' => ['/admin/qr', [UserRole::SuperAdmin, UserRole::Admin]],
            'kategori keperluan' => ['/admin/kategori-keperluan', [UserRole::SuperAdmin, UserRole::Admin]],
            'petugas' => ['/admin/petugas', [UserRole::SuperAdmin, UserRole::Admin]],
        ];
    }

    /**
     * @dataProvider staffRoutes
     *
     * @param  array<int, UserRole>  $allowed
     */
    public function test_route_is_limited_to_its_roles(string $uri, array $allowed): void
    {
        foreach (UserRole::cases() as $role) {
            // Setiap akun memakai sesi sendiri; tanpa ini auth.session melihat hash password akun sebelumnya.
            $this->flushSession();
            $response = $this->actingAs(User::factory()->role($role)->create())->get($uri);

            in_array($role, $allowed, true) ? $response->assertOk() : $response->assertForbidden();
        }
    }

    public function test_receptionist_and_pic_cannot_change_accounts(): void
    {
        $target = User::factory()->role(UserRole::Receptionist)->create();

        foreach ([UserRole::Receptionist, UserRole::Pic] as $role) {
            $this->flushSession();
            $actor = User::factory()->role($role)->create();

            $this->actingAs($actor)->post("/admin/pengguna/{$target->id}/status")->assertForbidden();
            $this->actingAs($actor)->post("/admin/pengguna/{$target->id}/buka-kunci")->assertForbidden();
            $this->actingAs($actor)->post('/admin/pengguna/tambah', [])->assertForbidden();
        }

        $this->assertTrue($target->refresh()->is_active);
    }

    public function test_guest_is_redirected_and_ajax_guest_gets_401(): void
    {
        $this->get('/admin/pengguna')->assertRedirect(route('login'));
        $this->getJson('/admin/pengguna')->assertUnauthorized();
    }
}
