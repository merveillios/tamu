<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji dashboard per peran (DSH-1 sampai DSH-5).
 */
class DashboardTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $locationA = $this->createLocation('Kantor A');
        $locationB = $this->createLocation('Kantor B');
        $department = $this->createDepartment($locationA);
        $qrA = $this->createQr($locationA, $this->createUser(UserRole::Receptionist));
        $qrB = $this->createQr($locationB, $this->createUser(UserRole::Receptionist));
        $categoryA = $this->createCategory($locationA);
        $categoryB = $this->createCategory($locationB);

        // Lokasi A: 2 belum ditugaskan (satu kemarin), 1 menunggu PIC, 1 direview, 1 selesai hari ini.
        $this->createVisit($qrA, $categoryA, ['name' => 'Tamu Menunggu Awal']);
        $this->createVisit($qrA, $categoryA, ['check_in_at' => now()->subDay(), 'visit_date' => now()->subDay()->toDateString()]);
        $this->createVisit($qrA, $categoryA, ['assigned_department_id' => $department->id]);
        $this->createVisit($qrA, $categoryA, ['status' => 'processing', 'assigned_department_id' => $department->id]);
        $this->createVisit($qrA, $categoryA, ['status' => 'completed', 'completed_at' => now()]);
        $this->createVisit($qrB, $categoryB, ['name' => 'Tamu Lokasi B']);

        $this->data = [
            'adminA' => $this->createAdmin($locationA),
            'superAdmin' => $this->createUser(UserRole::SuperAdmin),
            'receptionist' => $this->createUser(UserRole::Receptionist),
            'pic' => $this->createPic($department),
        ];
    }

    public function test_admin_counts_follow_definitions_and_scope(): void
    {
        $counts = app(DashboardService::class)->counts($this->data['adminA']);

        $this->assertSame([
            'unassigned' => 2,
            'waiting_pic' => 1,
            'processing' => 1,
            'completed_today' => 1,
            'today' => 4,
        ], $counts);
    }

    public function test_admin_dashboard_shows_cards_and_short_active_list(): void
    {
        $this->actingAs($this->data['adminA'])->get('/admin')
            ->assertOk()
            ->assertSee('Belum Ditugaskan')
            ->assertSee('Total Kunjungan Hari Ini')
            ->assertSee('Tamu Menunggu Awal')
            ->assertDontSee('Tamu Lokasi B')
            ->assertSee('Lihat semua');
    }

    public function test_super_admin_dashboard_summarises_all_locations(): void
    {
        $overview = app(DashboardService::class)->overview($this->data['superAdmin']);
        $this->assertSame(['total' => 6, 'active_locations' => 2, 'today' => 5, 'waiting' => 4], $overview);

        $chart = app(DashboardService::class)->dailyVisits($this->data['superAdmin']);
        $this->assertCount(14, $chart['labels']);
        $this->assertSame([1, 5], array_slice($chart['values'], -2));

        $this->actingAs($this->data['superAdmin'])->get('/admin')
            ->assertOk()
            ->assertSee('Ringkasan per lokasi')
            ->assertSee('Kantor B')
            ->assertSee('data-visits-chart', false)
            ->assertSee('assets/vendor/chart.js/chart.umd.js');
    }

    public function test_receptionist_and_pic_are_redirected_to_their_work_page(): void
    {
        $this->actingAs($this->data['receptionist'])->get('/admin')->assertRedirect(route('admin.visits.active'));
        $this->flushSession();
        $this->actingAs($this->data['pic'])->get('/admin')->assertRedirect(route('admin.assignments.index'));
    }

    public function test_admin_without_location_sees_notice(): void
    {
        $this->actingAs($this->createAdmin())->get('/admin')
            ->assertOk()
            ->assertSee('Anda belum ditugaskan ke lokasi mana pun.');
    }
}
