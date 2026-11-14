<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\GuestVisit;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\VisitStatusLog;
use App\Services\PicAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji Audit Log PIC dan ekspor PDF-nya (AUD-4 sampai AUD-6, revisi HRD #8 dan #9).
 */
class PicAuditTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $location = $this->createLocation();
        $departmentA = $this->createDepartment($location, 'Bidang Perdagangan');
        $departmentB = $this->createDepartment($location, 'Bidang Industri');
        $receptionist = $this->createUser(UserRole::Receptionist);
        $qr = $this->createQr($location, $receptionist);
        $category = $this->createCategory($location, $departmentA);
        // Satu akun PIC per Bidang/UPT dipakai bersama oleh beberapa orang.
        $picA = $this->createUser(UserRole::Pic, ['name' => 'PIC Bidang Perdagangan', 'department_id' => $departmentA->id]);
        $picB = $this->createUser(UserRole::Pic, ['name' => 'PIC Bidang Industri', 'department_id' => $departmentB->id]);
        $andri = StaffMember::create(['department_id' => $departmentA->id, 'name' => 'Andri']);
        $budi = StaffMember::create(['department_id' => $departmentA->id, 'name' => 'Budi']);
        $sari = StaffMember::create(['department_id' => $departmentB->id, 'name' => 'Sari']);

        $laura = $this->createVisit($qr, $category, ['name' => 'Laura', 'organization' => 'PT Megakarya Mas', 'purpose' => 'Konsultasi izin usaha perdagangan', 'status' => 'completed', 'result' => 'Berkas sudah diterima dan diproses.', 'served_by_staff_id' => $andri->id]);
        $dewi = $this->createVisit($qr, $category, ['name' => 'Dewi N', 'organization' => 'Kemendag', 'purpose' => 'Koordinasi program', 'status' => 'processing', 'served_by_staff_id' => $budi->id]);
        $rizki = $this->createVisit($qr, $category, ['name' => 'Rizki Pratama', 'organization' => 'PT Petrokimia Gresik', 'purpose' => 'Pengajuan berkas', 'status' => 'processing', 'served_by_staff_id' => $sari->id]);

        $this->log($laura, 'waiting', 'completed', $picA, '2026-11-13 09:10:00', $andri);
        $this->log($rizki, 'waiting', 'processing', $picB, '2026-11-13 09:45:00', $sari);
        // Log lama tanpa salinan petugas: nama diambil dari orang yang ditemui pada kunjungan.
        $this->log($dewi, 'waiting', 'processing', $picA, '2026-11-13 10:20:00', null);
        // Aksi resepsionis bukan aksi PIC dan tidak ikut.
        $this->log($rizki, 'waiting', 'cancelled', $receptionist, '2026-11-13 10:00:00', null);

        $this->data = compact('departmentA', 'departmentB', 'picA', 'andri', 'budi', 'sari', 'laura') + ['admin' => $this->createAdmin()];
    }

    public function test_page_shows_only_pic_actions_with_seven_columns(): void
    {
        $this->actingAs($this->data['admin'])->get('/admin/audit-log-pic')
            ->assertOk()
            ->assertSeeInOrder(['No', 'Waktu', 'PIC', 'Tamu', 'Instansi', 'Rincian Keperluan', 'Hasil'])
            ->assertSee('Konsultasi izin usaha perdagangan')
            ->assertSee('3 aksi PIC')
            ->assertSee('13/11/2026 09:10')
            ->assertSee('PT Megakarya Mas')
            ->assertSee('Berkas sudah diterima dan diproses.')
            ->assertDontSee('13/11/2026 10:00');
    }

    public function test_pic_column_shows_person_met_not_shared_account(): void
    {
        $rows = app(PicAuditService::class)->rows(app(PicAuditService::class)->query([])->get());

        $this->assertSame([
            ['1', '13/11/2026 10:20', 'Budi', 'Dewi N', 'Kemendag', 'Koordinasi program', '–'],
            ['2', '13/11/2026 09:45', 'Sari', 'Rizki Pratama', 'PT Petrokimia Gresik', 'Pengajuan berkas', '–'],
            ['3', '13/11/2026 09:10', 'Andri', 'Laura', 'PT Megakarya Mas', 'Konsultasi izin usaha perdagangan', 'Berkas sudah diterima dan diproses.'],
        ], $rows->pluck('values')->all());
        $this->assertStringNotContainsString('PIC Bidang', json_encode($rows->all()));
    }

    public function test_recompleted_visit_keeps_older_result_on_older_entry(): void
    {
        $laura = $this->data['laura'];
        GuestVisit::whereKey($laura->id)->update(['result' => 'Hasil baru setelah dibuka kembali.']);
        $this->log($laura, 'processing', 'completed', $this->data['picA'], '2026-11-13 11:30:00', $this->data['andri'], 'Hasil sebelumnya: Berkas sudah diterima dan diproses.');

        $results = app(PicAuditService::class)->rows(app(PicAuditService::class)->query(['staff_member_id' => $this->data['andri']->id])->get())
            ->map(fn (array $row) => [$row['values'][1], $row['values'][6]])->all();

        $this->assertSame([
            ['13/11/2026 11:30', 'Hasil baru setelah dibuka kembali.'],
            ['13/11/2026 09:10', 'Berkas sudah diterima dan diproses.'],
        ], $results);
    }

    public function test_filters(): void
    {
        $admin = $this->data['admin'];

        // Satu akun, dua orang: filter PIC memisahkan Andri dan Budi.
        $this->actingAs($admin)->get('/admin/audit-log-pic?staff_member_id='.$this->data['andri']->id)
            ->assertSee('Laura')->assertDontSee('Dewi N')->assertDontSee('Rizki Pratama');
        $this->actingAs($admin)->get('/admin/audit-log-pic?staff_member_id='.$this->data['budi']->id)
            ->assertSee('Dewi N')->assertDontSee('PT Megakarya Mas');
        $this->actingAs($admin)->get('/admin/audit-log-pic?department_id='.$this->data['departmentB']->id)
            ->assertSee('Rizki Pratama')->assertDontSee('PT Megakarya Mas');
        $this->actingAs($admin)->get('/admin/audit-log-pic?q=Petrokimia')->assertSee('Rizki Pratama')->assertDontSee('PT Megakarya Mas');
        $this->actingAs($admin)->get('/admin/audit-log-pic?from=2026-11-14')->assertSee('0 aksi PIC');
        $this->actingAs($admin)->get('/admin/audit-log-pic?from=2026-11-13&to=2026-11-13')->assertSee('3 aksi PIC');

        $this->actingAs($admin)->from('/admin/audit-log-pic')->get('/admin/audit-log-pic?staff_member_id=999999')
            ->assertSessionHasErrors('staff_member_id');
    }

    public function test_pdf_export_follows_filters_and_is_audited(): void
    {
        $response = $this->actingAs($this->data['admin'])->get('/admin/audit-log-pic/export?staff_member_id='.$this->data['andri']->id)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertMatchesRegularExpression('/audit-log-pic_\d{8}-\d{4}\.pdf/', $response->headers->get('Content-Disposition'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'pic_audit.export', 'user_id' => $this->data['admin']->id, 'detail->rows' => 1]);
    }

    public function test_pdf_template_has_one_table_with_seven_columns(): void
    {
        $service = app(PicAuditService::class);
        $html = view('admin.export.pic-audit', [
            'columns' => PicAuditService::COLUMNS,
            'widths' => PicAuditService::COLUMN_WIDTHS,
            'rows' => $service->rows($service->query([])->get()),
            'filters' => $service->filterSummary(['staff_member_id' => $this->data['andri']->id, 'period' => 'hari_ini']),
            'rowCount' => 3,
        ])->render();

        $this->assertSame(1, substr_count($html, '<table'));
        $this->assertSame(7, substr_count($html, '<th '));
        $this->assertStringContainsString('PIC: Andri · Periode: Hari ini', $html);
    }

    public function test_pic_sees_only_own_department(): void
    {
        $picA = $this->data['picA'];

        $this->actingAs($picA)->get('/admin/audit-log-pic')
            ->assertOk()
            ->assertSee('2 aksi PIC')
            ->assertSee('hanya Bidang/UPT Bidang Perdagangan')
            ->assertSee('Laura')
            ->assertSee('Dewi N')
            ->assertDontSee('Rizki Pratama')
            ->assertDontSee('id="department_id"', false);

        // Parameter department_id dari browser diabaikan; tetap Bidang/UPT akun sendiri.
        $this->actingAs($picA)->get('/admin/audit-log-pic?department_id='.$this->data['departmentB']->id)
            ->assertSee('Laura')->assertDontSee('Rizki Pratama');

        // Petugas Bidang/UPT lain tidak dapat dipilih sebagai filter.
        $this->actingAs($picA)->from('/admin/audit-log-pic')->get('/admin/audit-log-pic?staff_member_id='.$this->data['sari']->id)
            ->assertSessionHasErrors('staff_member_id');

        $this->actingAs($picA)->get('/admin/audit-log-pic/export')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas('audit_logs', ['action' => 'pic_audit.export', 'user_id' => $picA->id, 'detail->rows' => 2]);
    }

    public function test_pic_without_department_sees_nothing(): void
    {
        $orphan = $this->createUser(UserRole::Pic, ['department_id' => null]);

        $this->actingAs($orphan)->get('/admin/audit-log-pic')->assertOk()->assertSee('0 aksi PIC');
    }

    public function test_receptionist_cannot_open(): void
    {
        $this->actingAs($this->createUser(UserRole::SuperAdmin))->get('/admin/audit-log-pic')->assertOk();
        $this->actingAs($this->createUser(UserRole::Receptionist))->get('/admin/audit-log-pic')->assertForbidden();
        $this->actingAs($this->createUser(UserRole::Receptionist))->get('/admin/audit-log-pic/export')->assertForbidden();
    }

    /**
     * Mencatat satu perubahan status dengan waktu dan salinan orang yang ditemui tertentu.
     */
    private function log(GuestVisit $visit, string $from, string $to, User $actor, string $at, ?StaffMember $staff, ?string $note = null): void
    {
        $log = VisitStatusLog::create([
            'visit_id' => $visit->id, 'location_id' => $visit->location_id,
            'from_status' => $from, 'to_status' => $to, 'changed_by' => $actor->id,
            'staff_member_id' => $staff?->id, 'note' => $note,
        ]);

        VisitStatusLog::query()->whereKey($log->id)->toBase()->update(['created_at' => $at]);
    }
}
