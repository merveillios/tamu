<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\GuestVisit;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji Super Admin pertama dari .env saat deploy (SuperAdminSeeder).
 */
class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    private function configure(?string $email, ?string $password, string $name = 'Super Admin Dinas'): void
    {
        config(['sitamu.initial_super_admin' => ['name' => $name, 'email' => $email, 'password' => $password]]);
    }

    public function test_creates_super_admin_from_env_once(): void
    {
        $this->configure('Admin@Disperindag.Test', 'kata-sandi-kuat-2026');

        $this->seed(SuperAdminSeeder::class);

        $user = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $this->assertSame(['Super Admin Dinas', 'admin@disperindag.test', true], [$user->name, $user->email, $user->is_active]);
        $this->assertTrue(Hash::check('kata-sandi-kuat-2026', $user->password));

        // Dijalankan lagi dengan isian lain: tidak membuat akun baru dan tidak menimpa password.
        $this->configure('lain@disperindag.test', 'password-baru-yang-panjang');
        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, User::where('role', UserRole::SuperAdmin)->count());
        $this->assertTrue(Hash::check('kata-sandi-kuat-2026', $user->fresh()->password));
    }

    public function test_skips_when_env_is_empty_or_password_is_weak(): void
    {
        $this->configure(null, null);
        $this->seed(SuperAdminSeeder::class);

        $this->configure('admin@disperindag.test', 'pendek');
        $this->seed(SuperAdminSeeder::class);

        $this->configure('admin@disperindag.test', 'password123');
        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_database_seeder_in_production_creates_only_super_admin(): void
    {
        $this->app['env'] = 'production';
        $this->configure('admin@disperindag.test', 'kata-sandi-kuat-2026');

        // Perintah deploy yang sama: db:seed di produksi wajib --force.
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertSame(['super_admin'], User::pluck('role')->map(fn ($role) => $role->value)->all());
        $this->assertSame(0, Location::count());
        $this->assertSame(0, GuestVisit::count());
    }
}
