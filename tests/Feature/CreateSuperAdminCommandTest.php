<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji perintah php artisan sitamu:create-superadmin.
 */
class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_super_admin_with_hashed_password_and_lowercase_email(): void
    {
        $this->runCommand('Admin Utama', 'Admin@Contoh.test', 'rahasia-panjang', 'rahasia-panjang')
            ->expectsOutputToContain('berhasil dibuat')
            ->assertSuccessful();

        $user = User::where('email', 'admin@contoh.test')->firstOrFail();

        $this->assertSame(UserRole::SuperAdmin, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('rahasia-panjang', $user->password));
    }

    public function test_rejects_short_password(): void
    {
        $this->runCommand('Admin Utama', 'admin@contoh.test', 'pendek', 'pendek')
            ->expectsOutput('Password minimal 8 karakter.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_rejects_mismatched_confirmation(): void
    {
        $this->runCommand('Admin Utama', 'admin@contoh.test', 'rahasia-panjang', 'rahasia-lain')
            ->expectsOutput('Konfirmasi password tidak cocok.')
            ->assertFailed();
    }

    public function test_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'admin@contoh.test']);

        $this->runCommand('Admin Kedua', 'admin@contoh.test', 'rahasia-panjang', 'rahasia-panjang')
            ->expectsOutput('Email sudah digunakan.')
            ->assertFailed();
    }

    /**
     * Menjalankan perintah dengan jawaban interaktif yang diberikan.
     */
    private function runCommand(string $name, string $email, string $password, string $confirmation)
    {
        return $this->artisan('sitamu:create-superadmin')
            ->expectsQuestion('Nama akun', $name)
            ->expectsQuestion('Email', $email)
            ->expectsQuestion('Password (minimal 8 karakter)', $password)
            ->expectsQuestion('Ulangi password', $confirmation);
    }
}
