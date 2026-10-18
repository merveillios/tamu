<?php

namespace Tests\Feature\Visits;

use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Models\GuestVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji penugasan dan transisi status kunjungan (RSP-2 sampai RSP-4, PIC-2, PIC-3, ADM-3, Bab 7).
 */
class VisitFlowTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $location = $this->createLocation();
        $departmentA = $this->createDepartment($location, 'Bidang A');
        $departmentB = $this->createDepartment($location, 'Bidang B');
        $departmentNoPic = $this->createDepartment($location, 'Bidang Tanpa PIC');
        $this->createStaffMember($departmentNoPic);
        $receptionist = $this->createUser(UserRole::Receptionist);
        $qr = $this->createQr($location, $receptionist);
        $category = $this->createCategory($location, $departmentA);

        $this->data = [
            'location' => $location,
            'departmentA' => $departmentA,
            'departmentB' => $departmentB,
            'departmentNoPic' => $departmentNoPic,
            'staffA' => $this->createStaffMember($departmentA),
            'staffB' => $this->createStaffMember($departmentB),
            'picA' => $this->createPic($departmentA),
            'picB' => $this->createPic($departmentB),
            'receptionist' => $receptionist,
            'admin' => $this->createAdmin($location),
            'visit' => $this->createVisit($qr, $category),
        ];
    }

    public function test_department_choices_only_include_departments_with_active_pic_and_staff(): void
    {
        $response = $this->as('receptionist')->getJson($this->url('departemen'))->assertOk();

        $names = collect($response->json('data.departments'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Bidang A', 'Bidang B'], $names);
        $this->assertSame($this->data['departmentA']->id, $response->json('data.selected_department_id'));
    }

    public function test_receptionist_assigns_department_and_person_to_meet(): void
    {
        $this->assign('departmentA', 'staffA')->assertOk()->assertJson(['ok' => true]);

        $visit = $this->visit();
        $this->assertSame(VisitStatus::Waiting, $visit->status);
        $this->assertSame([$this->data['departmentA']->id, $this->data['staffA']->id, $this->data['receptionist']->id], [
            $visit->assigned_department_id, $visit->served_by_staff_id, $visit->assigned_by,
        ]);
        $this->assertSame('Menunggu PIC', $visit->display_status['label']);
        $this->assertDatabaseCount('visit_assignments', 1);
        $this->assertDatabaseCount('visit_status_logs', 0);
    }

    public function test_staff_must_belong_to_selected_department(): void
    {
        $this->assign('departmentA', 'staffB')->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->assign('departmentNoPic', 'staffA')->assertStatus(422);

        $this->assertNull($this->visit()->assigned_department_id);
    }

    public function test_reassignment_is_allowed_until_pic_accepts(): void
    {
        $this->assign('departmentA', 'staffA')->assertOk();
        $this->assign('departmentB', 'staffB')->assertOk();
        $this->assertDatabaseCount('visit_assignments', 2);

        $this->as('picB')->postJson($this->url('terima'))->assertOk();

        $this->assign('departmentA', 'staffA')
            ->assertStatus(409)
            ->assertJsonPath('error.message', 'Kunjungan ini sudah diterima PIC. Daftar akan dimuat ulang.');
    }

    public function test_pic_accepts_assignment(): void
    {
        $this->assign('departmentA', 'staffA');

        $this->as('picA')->postJson($this->url('terima'))->assertOk();

        $visit = $this->visit();
        $this->assertSame(VisitStatus::Processing, $visit->status);
        $this->assertSame($this->data['picA']->id, $visit->processed_by);
        $this->assertDatabaseHas('visit_status_logs', [
            'visit_id' => $visit->id, 'from_status' => 'waiting', 'to_status' => 'processing',
            'changed_by' => $this->data['picA']->id, 'staff_member_id' => $this->data['staffA']->id,
        ]);
    }

    public function test_pic_of_previous_department_gets_409_without_access_denied(): void
    {
        $this->assign('departmentA', 'staffA');
        $this->assign('departmentB', 'staffB');

        $this->as('picA')->postJson($this->url('terima'))
            ->assertStatus(409)
            ->assertJsonPath('error.message', 'Kunjungan ini sudah dialihkan. Daftar akan dimuat ulang.');

        $this->assertDatabaseMissing('audit_logs', ['action' => 'access.denied']);
    }

    public function test_assignment_cannot_be_accepted_twice(): void
    {
        $this->assign('departmentA', 'staffA');
        $this->as('picA')->postJson($this->url('terima'))->assertOk();

        $this->as('picA')->postJson($this->url('terima'))->assertStatus(409);
    }

    public function test_pic_cannot_accept_unassigned_visit(): void
    {
        $this->as('picA')->postJson($this->url('terima'))->assertNotFound();
    }

    public function test_pic_completes_with_result(): void
    {
        $this->assign('departmentA', 'staffA');
        $this->as('picA')->postJson($this->url('terima'));

        $this->as('picA')->postJson($this->url('selesai'), ['result' => '    '])->assertStatus(422);
        $this->as('picA')->postJson($this->url('selesai'), ['result' => 'Izin sudah diproses dan dikirim lewat email.'])->assertOk();

        $visit = $this->visit();
        $this->assertSame(VisitStatus::Completed, $visit->status);
        $this->assertSame('Izin sudah diproses dan dikirim lewat email.', $visit->result);
        $this->assertSame($this->data['picA']->id, $visit->closed_by);
        $this->assertNotNull($visit->completed_at);
    }

    public function test_waiting_visit_cannot_be_completed(): void
    {
        $this->assign('departmentA', 'staffA');

        $this->as('picA')->postJson($this->url('selesai'), ['result' => 'Hasil langsung'])->assertStatus(409);
        $this->assertSame(VisitStatus::Waiting, $this->visit()->status);
    }

    public function test_cancel_requires_reason_and_text_for_other(): void
    {
        $this->as('receptionist')->postJson($this->url('batal'), ['reason' => 'other'])->assertStatus(422);

        $this->as('receptionist')->postJson($this->url('batal'), ['reason' => 'other', 'reason_text' => 'Tamu salah jadwal'])->assertOk();

        $visit = $this->visit();
        $this->assertSame(VisitStatus::Cancelled, $visit->status);
        $this->assertSame('Lainnya: Tamu salah jadwal', $visit->cancel_reason);
        $this->assertSame($this->data['receptionist']->id, $visit->closed_by);
    }

    public function test_admin_can_cancel_processing_visit(): void
    {
        $this->assign('departmentA', 'staffA');
        $this->as('picA')->postJson($this->url('terima'));

        $this->as('admin')->postJson($this->url('batal'), ['reason' => 'not_present'])->assertOk();

        $this->assertDatabaseHas('visit_status_logs', ['from_status' => 'processing', 'to_status' => 'cancelled']);
    }

    public function test_closed_visit_cannot_be_cancelled_or_assigned_again(): void
    {
        $this->as('receptionist')->postJson($this->url('batal'), ['reason' => 'duplicate'])->assertOk();

        $this->as('receptionist')->postJson($this->url('batal'), ['reason' => 'duplicate'])->assertStatus(409);
        $this->assign('departmentA', 'staffA')->assertStatus(422)->assertJsonPath('error.code', 'INVALID_TRANSITION');
    }

    public function test_pic_cannot_cancel_or_assign(): void
    {
        $this->as('picA')->postJson($this->url('batal'), ['reason' => 'duplicate'])->assertForbidden();
        $this->as('picA')->postJson($this->url('tugaskan'), ['department_id' => 1, 'staff_member_id' => 1])->assertForbidden();
    }

    public function test_admin_reopens_completed_visit_to_previous_status(): void
    {
        $this->assign('departmentA', 'staffA');
        $this->as('picA')->postJson($this->url('terima'));
        $this->as('picA')->postJson($this->url('selesai'), ['result' => 'Hasil pertama yang lengkap']);

        $this->as('admin')->postJson($this->url('buka-kembali'), ['note' => 'Hasil perlu dilengkapi'])->assertOk();

        $visit = $this->visit();
        $this->assertSame(VisitStatus::Processing, $visit->status);
        $this->assertNull($visit->completed_at);
        $this->assertNull($visit->closed_by);
        $this->assertSame('Hasil pertama yang lengkap', $visit->result);
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.reopen', 'entity_id' => $visit->id]);

        $this->as('picA')->postJson($this->url('selesai'), ['result' => 'Hasil kedua yang diperbarui'])->assertOk();
        $this->assertDatabaseHas('visit_status_logs', ['visit_id' => $visit->id, 'note' => 'Hasil sebelumnya: Hasil pertama yang lengkap']);
    }

    public function test_cancelled_visit_reopens_to_waiting(): void
    {
        $this->as('receptionist')->postJson($this->url('batal'), ['reason' => 'not_present']);

        $this->as('admin')->postJson($this->url('buka-kembali'), ['note' => 'Tamu kembali'])->assertOk();

        $visit = $this->visit();
        $this->assertSame(VisitStatus::Waiting, $visit->status);
        $this->assertNull($visit->cancel_reason);
    }

    public function test_reopen_rules(): void
    {
        $this->as('admin')->postJson($this->url('buka-kembali'), ['note' => 'Coba'])->assertStatus(422);

        $this->as('receptionist')->postJson($this->url('batal'), ['reason' => 'not_present']);
        $superAdmin = $this->createUser(UserRole::SuperAdmin);
        $this->flushSession();
        $this->actingAs($superAdmin)->postJson($this->url('buka-kembali'), ['note' => 'Coba'])->assertForbidden();
        $this->as('admin')->postJson($this->url('buka-kembali'), [])->assertStatus(422);
    }

    /**
     * Bertindak sebagai salah satu akun data uji dengan sesi bersih.
     */
    private function as(string $key): static
    {
        $this->flushSession();

        return $this->actingAs($this->data[$key]);
    }

    private function assign(string $department, string $staff)
    {
        return $this->as('receptionist')->postJson($this->url('tugaskan'), [
            'department_id' => $this->data[$department]->id,
            'staff_member_id' => $this->data[$staff]->id,
        ]);
    }

    private function url(string $action): string
    {
        return "/admin/kunjungan/{$this->data['visit']->id}/{$action}";
    }

    private function visit(): GuestVisit
    {
        return $this->data['visit']->refresh();
    }
}
