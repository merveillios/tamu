<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Menguji middleware SecurityHeaders, active, dan role.
 */
class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'active', 'role:admin,super_admin'])
            ->get('/_tes/khusus-admin', fn () => 'ok');
        Route::middleware('web')->get('/tamu/_tes', fn () => 'ok');
    }

    public function test_security_headers_are_set_on_web_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $this->get('/')->headers->get('Content-Security-Policy'));
    }

    public function test_guest_pages_send_no_referrer(): void
    {
        $this->get('/tamu/_tes')->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_role_middleware_allows_listed_roles(): void
    {
        foreach ([UserRole::Admin, UserRole::SuperAdmin] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get('/_tes/khusus-admin')
                ->assertOk();
        }
    }

    public function test_role_middleware_rejects_other_roles_with_403(): void
    {
        foreach ([UserRole::Receptionist, UserRole::Pic] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get('/_tes/khusus-admin')
                ->assertForbidden();
        }
    }

    public function test_inactive_account_is_logged_out_and_redirected_to_login(): void
    {
        $this->actingAs(User::factory()->inactive()->create())
            ->get('/_tes/khusus-admin')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_inactive_account_gets_401_on_ajax(): void
    {
        $this->actingAs(User::factory()->inactive()->create())
            ->getJson('/_tes/khusus-admin')
            ->assertUnauthorized();
    }
}
