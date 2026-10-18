<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji login, penguncian akun, rate limit, dan logout (AUTH-1, AUTH-3).
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password-benar-123';

    public function test_valid_credentials_log_in_and_reset_failure_counter(): void
    {
        $user = $this->staff(['failed_logins' => 2]);

        $this->post('/login', ['email' => 'STAF@contoh.test', 'password' => self::PASSWORD])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertSame(0, $user->failed_logins);
        $this->assertNotNull($user->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_success', 'user_id' => $user->id]);
    }

    public function test_wrong_password_shows_uniform_message_and_counts_failure(): void
    {
        $user = $this->staff();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
        $this->assertSame(1, $user->refresh()->failed_logins);
    }

    public function test_account_is_locked_after_max_attempts_even_for_correct_password(): void
    {
        $user = $this->staff();

        for ($i = 0; $i < config('sitamu.login.max_attempts'); $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah']);
        }

        $this->assertTrue($user->refresh()->locked_until->isFuture());
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.locked', 'user_id' => $user->id]);

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        $this->assertGuest();
    }

    public function test_lock_expires_after_lock_minutes(): void
    {
        $user = $this->staff(['locked_until' => now()->addMinutes(config('sitamu.login.lock_minutes'))]);

        $this->travel(config('sitamu.login.lock_minutes') + 1)->minutes();

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = $this->staff(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_unknown_email_is_audited_without_account(): void
    {
        $this->post('/login', ['email' => 'tidak.ada@contoh.test', 'password' => 'apa-saja'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $log = AuditLog::where('action', 'auth.login_failed')->firstOrFail();
        $this->assertNull($log->user_id);
        $this->assertArrayNotHasKey('password', $log->detail);
    }

    public function test_login_is_rate_limited_per_ip(): void
    {
        config(['sitamu.login.rate_limit_max' => 2]);

        $this->post('/login', ['email' => 'a@contoh.test', 'password' => 'x']);
        $this->post('/login', ['email' => 'a@contoh.test', 'password' => 'x']);

        $this->post('/login', ['email' => 'a@contoh.test', 'password' => 'x'])->assertStatus(429);
    }

    public function test_logout_ends_session(): void
    {
        $user = $this->staff();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout', 'user_id' => $user->id]);
    }

    /**
     * Membuat akun staf dengan password yang diketahui.
     */
    private function staff(array $attributes = []): User
    {
        return User::factory()->role(UserRole::Admin)->create(array_merge([
            'email' => 'staf@contoh.test',
            'password' => self::PASSWORD,
        ], $attributes));
    }
}
