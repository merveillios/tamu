<?php

namespace Tests\Feature\Visits;

use App\Enums\UserRole;
use App\Models\VisitSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji cakupan akses Tamu Aktif, Penugasan, detail, dan tanda tangan (A-15, Bab 12.2).
 */
class VisitAccessTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $locationA = $this->createLocation('Lokasi A');
        $locationB = $this->createLocation('Lokasi B');
        $departmentA = $this->createDepartment($locationA);
        $departmentB = $this->createDepartment($locationB);
        $receptionistA = $this->createUser(UserRole::Receptionist);
        $receptionistB = $this->createUser(UserRole::Receptionist);
        $qrA = $this->createQr($locationA, $receptionistA);
        $qrB = $this->createQr($locationB, $receptionistB);

        $visitA = $this->createVisit($qrA, $this->createCategory($locationA), [
            'name' => 'Tamu Lokasi A', 'phone' => '6281200000001', 'assigned_department_id' => $departmentA->id,
        ]);
        $visitB = $this->createVisit($qrB, $this->createCategory($locationB), ['name' => 'Tamu Lokasi B', 'assigned_department_id' => $departmentB->id]);
        VisitSignature::create(['visit_id' => $visitA->id, 'image' => 'png', 'size_bytes' => 3, 'sha256' => str_repeat('a', 64)]);

        $this->data = compact('locationA', 'locationB', 'receptionistA', 'visitA', 'visitB') + [
            'admin' => $this->createAdmin(),
            'picA' => $this->createPic($departmentA),
            'superAdmin' => $this->createUser(UserRole::SuperAdmin),
        ];
    }

    public function test_receptionist_data_contains_only_own_qr_visits_without_private_fields(): void
    {
        $response = $this->as('receptionistA')->getJson('/admin/tamu/data')->assertOk();

        $rows = $response->json('data.rows');
        $this->assertSame(['Tamu Lokasi A'], array_column($rows, 'name'));
        $this->assertArrayNotHasKey('phone', $rows[0]);
        $this->assertArrayNotHasKey('email', $rows[0]);
        $this->assertTrue($rows[0]['actions']['assign']);
        $this->assertSame(1, $response->json('data.counts.menunggu_pic'));
    }

    public function test_super_admin_and_admin_see_all_locations(): void
    {
        $this->assertCount(2, $this->as('superAdmin')->getJson('/admin/tamu/data')->json('data.rows'));

        $adminRows = $this->as('admin')->getJson('/admin/tamu/data')->json('data.rows');
        $this->assertEqualsCanonicalizing(['Tamu Lokasi A', 'Tamu Lokasi B'], array_column($adminRows, 'name'));
        $this->assertFalse($adminRows[0]['actions']['assign']);
    }

    public function test_admin_can_filter_any_location(): void
    {
        $rows = $this->as('admin')->getJson('/admin/tamu/data?location_id='.$this->data['locationB']->id)->json('data.rows');

        $this->assertSame(['Tamu Lokasi B'], array_column($rows, 'name'));
    }

    public function test_search_matches_phone_typed_with_leading_zero(): void
    {
        $rows = $this->as('receptionistA')->getJson('/admin/tamu/data?q=081200000001')->json('data.rows');

        $this->assertCount(1, $rows);
    }

    public function test_pic_data_groups_assignments(): void
    {
        $response = $this->as('picA')->getJson('/admin/penugasan/data')->assertOk();

        $this->assertSame(['Tamu Lokasi A'], array_column($response->json('data.new'), 'name'));
        $this->assertSame([], $response->json('data.reviewing'));
        $this->assertSame(1, $response->json('data.new_count'));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function outOfScopeRequests(): array
    {
        return [
            'resepsionis membuka detail' => ['receptionistA', 'GET', 'detail'],
            'resepsionis menugaskan' => ['receptionistA', 'POST', 'tugaskan'],
            'resepsionis memuat departemen' => ['receptionistA', 'GET', 'departemen'],
            'pic membuka detail' => ['picA', 'GET', 'detail'],
            'pic menerima' => ['picA', 'POST', 'terima'],
            'resepsionis melihat tanda tangan' => ['receptionistA', 'GET', 'tanda-tangan'],
        ];
    }

    /**
     * @dataProvider outOfScopeRequests
     */
    public function test_ids_outside_scope_get_404_and_access_denied(string $actor, string $method, string $action): void
    {
        $id = $this->data['visitB']->id;
        $uri = $action === 'detail' ? "/admin/kunjungan/{$id}" : "/admin/kunjungan/{$id}/{$action}";
        $payload = ['reason' => 'duplicate', 'department_id' => 1, 'staff_member_id' => 1];

        $this->as($actor)->json($method, $uri, $method === 'POST' ? $payload : [])->assertNotFound();

        $this->assertDatabaseHas('audit_logs', ['action' => 'access.denied', 'user_id' => $this->data[$actor]->id, 'detail->id' => $id]);
    }

    public function test_detail_shows_signature_to_admin_and_receptionist(): void
    {
        $id = $this->data['visitA']->id;

        $this->as('admin')->get("/admin/kunjungan/{$id}")->assertOk()->assertSee('tanda-tangan')->assertSee('081200000001');
        $this->as('receptionistA')->get("/admin/kunjungan/{$id}")->assertOk()->assertSee('tanda-tangan');
        $this->as('picA')->get("/admin/kunjungan/{$id}")->assertOk()->assertDontSee('tanda-tangan');
    }

    public function test_signature_route_is_limited_to_admin_and_receptionist(): void
    {
        $id = $this->data['visitA']->id;

        $this->as('admin')->get("/admin/kunjungan/{$id}/tanda-tangan")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->as('receptionistA')->get("/admin/kunjungan/{$id}/tanda-tangan")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->as('picA')->get("/admin/kunjungan/{$id}/tanda-tangan")->assertForbidden();
    }

    public function test_receptionist_without_qr_sees_notice(): void
    {
        $this->flushSession();
        $this->actingAs($this->createUser(UserRole::Receptionist))->get('/admin/tamu')
            ->assertOk()
            ->assertSee('Akun ini belum dihubungkan ke QR mana pun.');
    }

    public function test_pic_without_department_sees_notice_and_menu_badge(): void
    {
        $this->flushSession();
        $this->actingAs($this->createUser(UserRole::Pic))->get('/admin/penugasan')
            ->assertOk()
            ->assertSee('Akun ini belum dihubungkan ke departemen.');

        $this->as('picA')->get('/admin/penugasan')->assertSee('data-assignment-badge', false);
    }

    private function as(string $key): static
    {
        $this->flushSession();

        return $this->actingAs($this->data[$key]);
    }
}
