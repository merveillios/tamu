<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji pencatatan dan halaman audit log (AUD-1, AUD-2).
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_keys_are_never_stored(): void
    {
        $log = app(AuditService::class)->record('tes.aksi', null, [
            'password' => 'rahasia',
            'token' => 'abc',
            'result' => 'hasil layanan',
            'signature' => 'data:image/png;base64,xyz',
            'format' => 'csv',
        ]);

        $this->assertSame(['format' => 'csv'], $log->refresh()->detail);
    }

    public function test_page_filters_by_action_and_paginates_50_rows(): void
    {
        $superAdmin = User::factory()->role(UserRole::SuperAdmin)->create();

        for ($i = 0; $i < 55; $i++) {
            AuditLog::create(['action' => 'auth.login_success', 'user_id' => $superAdmin->id]);
        }
        AuditLog::create(['action' => 'user.unlock', 'user_id' => $superAdmin->id]);

        $page = $this->actingAs($superAdmin)->get('/admin/audit-log')->assertOk();
        $this->assertSame(50, $page->viewData('logs')->count());
        $this->assertSame('user.unlock', $page->viewData('logs')->first()->action);

        $filtered = $this->actingAs($superAdmin)->get('/admin/audit-log?action=user.unlock')->assertOk();
        $this->assertSame(1, $filtered->viewData('logs')->total());
    }

    public function test_page_filters_by_date_range(): void
    {
        $superAdmin = User::factory()->role(UserRole::SuperAdmin)->create();
        $this->travelTo(now()->subDays(3));
        AuditLog::create(['action' => 'auth.logout', 'user_id' => $superAdmin->id]);
        $this->travelBack();

        $response = $this->actingAs($superAdmin)->get('/admin/audit-log?from='.now()->toDateString());

        $this->assertSame(0, $response->viewData('logs')->total());
    }
}
