<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji ganti password sendiri dan pengakhiran sesi (USR-3).
 */
class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password-lama-123';

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->staff();

        $this->actingAs($user)
            ->post('/admin/password', ['current_password' => 'salah', 'password' => 'password-baru-123', 'password_confirmation' => 'password-baru-123'])
            ->assertSessionHasErrors(['current_password' => 'Password lama salah.']);

        $this->assertTrue(Hash::check(self::PASSWORD, $user->refresh()->password));
    }

    public function test_changing_password_logs_out_current_session(): void
    {
        $user = $this->staff();

        $this->actingAs($user)
            ->post('/admin/password', ['current_password' => self::PASSWORD, 'password' => 'password-baru-123', 'password_confirmation' => 'password-baru-123'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Password berhasil diganti. Silakan login kembali.');

        $this->assertGuest();
        $this->assertTrue(Hash::check('password-baru-123', $user->refresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.password_changed', 'user_id' => $user->id]);
    }

    public function test_other_sessions_are_rejected_after_password_changes(): void
    {
        $user = $this->staff();

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
        $this->get('/admin/password')->assertOk();

        // Password diganti dari perangkat lain atau direset Super Admin.
        $user->forceFill(['password' => 'password-baru-123'])->save();

        // Request nyata selalu memuat ulang akun dari database; di tes guard menyimpan akun lama di memori.
        $this->app['auth']->forgetGuards();

        $this->get('/admin/password')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Membuat akun staf dengan password yang diketahui.
     */
    private function staff(): User
    {
        return User::factory()->role(UserRole::Receptionist)->create(['password' => self::PASSWORD]);
    }
}
