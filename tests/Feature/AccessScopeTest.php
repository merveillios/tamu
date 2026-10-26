<?php

namespace Tests\Feature;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\GuestVisit;
use App\Models\Location;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\StaffMember;
use App\Services\AccessScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji AccessScope untuk keempat peran staf (PRD Bab 12.2, A-15).
 */
class AccessScopeTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $locationA = $this->createLocation('Lokasi A');
        $locationB = $this->createLocation('Lokasi B');
        $departmentA = $this->createDepartment($locationA, 'Bidang A');
        $departmentB = $this->createDepartment($locationB, 'Bidang B');
        $receptionistA = $this->createUser(UserRole::Receptionist);
        $receptionistB = $this->createUser(UserRole::Receptionist);
        $qrA = $this->createQr($locationA, $receptionistA);
        $qrAInactive = $this->createQr($locationA, $receptionistA, QrStatus::Inactive);
        $qrB = $this->createQr($locationB, $receptionistB);
        $categoryA = $this->createCategory($locationA, $departmentA);
        $categoryB = $this->createCategory($locationB, $departmentB);
        $this->createStaffMember($departmentA);
        $this->createStaffMember($departmentB);

        $this->data = [
            'locationA' => $locationA,
            'departmentA' => $departmentA,
            'qrA' => $qrA,
            'qrAInactive' => $qrAInactive,
            'receptionistA' => $receptionistA,
            'admin' => $this->createAdmin(),
            'picA' => $this->createPic($departmentA),
            'superAdmin' => $this->createUser(UserRole::SuperAdmin),
            'visitA' => $this->createVisit($qrA, $categoryA, ['assigned_department_id' => $departmentA->id]),
            'visitAInactiveQr' => $this->createVisit($qrAInactive, $categoryA),
            'visitB' => $this->createVisit($qrB, $categoryB, ['assigned_department_id' => $departmentB->id]),
        ];
    }

    public function test_super_admin_and_admin_see_everything(): void
    {
        foreach (['superAdmin', 'admin'] as $actor) {
            $user = $this->data[$actor];

            $this->assertSame(3, GuestVisit::forUser($user)->count());
            $this->assertSame(3, QrCode::forUser($user)->count());
            $this->assertSame(2, Location::forUser($user)->count());
            $this->assertSame(2, Department::forUser($user)->count());
            $this->assertSame(2, PurposeCategory::forUser($user)->count());
            $this->assertSame(2, StaffMember::forUser($user)->count());
            $this->assertNull(app(AccessScope::class)->locationIds($user));
        }
    }

    public function test_receptionist_sees_visits_from_own_qr_of_any_status(): void
    {
        $user = $this->data['receptionistA'];

        $this->assertVisitIds(['visitA', 'visitAInactiveQr'], $user);
        $this->assertSame(0, QrCode::forUser($user)->count());
        $this->assertSame(0, Department::forUser($user)->count());
    }

    public function test_pic_sees_only_visits_assigned_to_its_department(): void
    {
        $user = $this->data['picA'];

        $this->assertVisitIds(['visitA'], $user);
        $this->assertSame([$this->data['locationA']->id], app(AccessScope::class)->locationIds($user));
        $this->assertSame(0, StaffMember::forUser($user)->count());
    }

    public function test_accounts_without_scope_see_nothing(): void
    {
        $this->assertSame(0, GuestVisit::forUser($this->createUser(UserRole::Receptionist))->count());
        $this->assertSame(0, GuestVisit::forUser($this->createUser(UserRole::Pic))->count());
    }

    public function test_find_or_deny_returns_data_in_scope(): void
    {
        $visit = app(AccessScope::class)->findOrDeny(GuestVisit::class, $this->data['visitA']->id, $this->data['receptionistA']);

        $this->assertTrue($visit->is($this->data['visitA']));
    }

    public function test_find_or_deny_out_of_scope_gives_404_and_audits(): void
    {
        foreach (['receptionistA', 'picA'] as $actor) {
            try {
                app(AccessScope::class)->findOrDeny(GuestVisit::class, $this->data['visitB']->id, $this->data[$actor]);
                $this->fail("{$actor} seharusnya ditolak.");
            } catch (NotFoundHttpException) {
                $this->assertDatabaseHas('audit_logs', ['action' => 'access.denied', 'user_id' => $this->data[$actor]->id]);
            }
        }
    }

    public function test_find_or_deny_missing_id_gives_404_without_audit(): void
    {
        $this->expectException(NotFoundHttpException::class);

        try {
            app(AccessScope::class)->findOrDeny(GuestVisit::class, 999999, $this->data['admin']);
        } finally {
            $this->assertDatabaseMissing('audit_logs', ['action' => 'access.denied']);
        }
    }

    /**
     * Memastikan kunjungan yang terlihat akun sama persis dengan daftar kunci data uji.
     *
     * @param  array<int, string>  $keys
     */
    private function assertVisitIds(array $keys, $user): void
    {
        $expected = array_map(fn (string $key) => $this->data[$key]->id, $keys);

        $this->assertEqualsCanonicalizing($expected, GuestVisit::forUser($user)->pluck('id')->all());
    }
}
