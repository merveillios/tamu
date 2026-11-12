<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\GuestVisit;
use App\Models\VisitSignature;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji History Kunjungan dan ekspor PDF buku tamu (HIS-1 sampai HIS-6, Bab 12.4, revisi HRD #3 dan #13).
 */
class HistoryExportTest extends TestCase
{
    use InteractsWithGuestPages;
    use RefreshDatabase;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $locationA = $this->createLocation('Kantor A');
        $locationB = $this->createLocation('Kantor B');
        $departmentA = $this->createDepartment($locationA, 'Bidang A');
        $receptionistA = $this->createUser(UserRole::Receptionist);
        $qrA = $this->createQr($locationA, $receptionistA);
        $qrB = $this->createQr($locationB, $this->createUser(UserRole::Receptionist));
        $categoryA = $this->createCategory($locationA, $departmentA);
        $categoryB = $this->createCategory($locationB);

        $completed = $this->createVisit($qrA, $categoryA, [
            'name' => 'Tamu Selesai', 'purpose' => 'Konsultasi ekspor kopi', 'status' => 'completed',
            'result' => 'Sudah dijelaskan', 'assigned_department_id' => $departmentA->id, 'vulnerable_group' => 'lansia',
        ]);
        $this->createVisit($qrA, $categoryA, ['name' => '=HYPERLINK("http://jahat.test")', 'status' => 'cancelled']);
        $this->createVisit($qrA, $categoryA, ['name' => 'Tamu Menunggu', 'status' => 'waiting']);
        $this->createVisit($qrA, $categoryA, ['name' => 'Tamu Direview', 'status' => 'processing']);
        $this->createVisit($qrB, $categoryB, ['name' => 'Tamu Lokasi B', 'status' => 'completed']);

        $png = base64_decode(substr($this->signatureDataUrl(), strlen('data:image/png;base64,')));
        VisitSignature::create(['visit_id' => $completed->id, 'image' => $png, 'size_bytes' => strlen($png), 'sha256' => hash('sha256', $png)]);

        $this->data = compact('locationA', 'locationB', 'departmentA', 'receptionistA') + [
            'admin' => $this->createAdmin(),
            'superAdmin' => $this->createUser(UserRole::SuperAdmin),
        ];
    }

    public function test_history_shows_all_but_cancelled_of_all_locations_by_default(): void
    {
        $this->actingAs($this->data['admin'])->get('/admin/history')
            ->assertOk()
            ->assertSee('Tamu Selesai')
            ->assertSee('Tamu Menunggu')
            ->assertSee('Tamu Direview')
            ->assertSee('Tamu Lokasi B')
            ->assertDontSee('HYPERLINK');
    }

    public function test_cancelled_visits_are_shown_only_by_filter_and_never_exported(): void
    {
        $admin = $this->data['admin'];

        $this->actingAs($admin)->get('/admin/history?status=dibatalkan')
            ->assertOk()
            ->assertSee('HYPERLINK')
            ->assertDontSee('Tamu Selesai')
            ->assertSee('Kunjungan Dibatalkan tidak masuk ekspor buku tamu.')
            ->assertDontSee('/admin/history/export', false);

        $this->actingAs($admin)->from('/admin/history?status=dibatalkan')->get('/admin/history/export?status=dibatalkan')
            ->assertRedirect('/admin/history?status=dibatalkan')
            ->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'visit.export']);
    }

    public function test_status_filter_can_show_waiting_visits(): void
    {
        $this->actingAs($this->data['admin'])->get('/admin/history?status=menunggu')
            ->assertSee('Tamu Menunggu')
            ->assertDontSee('Tamu Selesai');
    }

    public function test_receptionist_history_only_shows_visits_from_own_qr(): void
    {
        $receptionist = $this->data['receptionistA'];

        $this->actingAs($receptionist)->get('/admin/history')
            ->assertOk()
            ->assertSee('Tamu Selesai')
            ->assertSee('hanya dari QR milik Anda')
            ->assertSee('Ekspor PDF')
            ->assertDontSee('Tamu Lokasi B');

        $this->actingAs($receptionist)->get('/admin/history?location_id='.$this->data['locationA']->id)->assertOk();
        $this->actingAs($receptionist)->get('/admin/history?location_id='.$this->data['locationB']->id)->assertNotFound();
    }

    public function test_receptionist_export_contains_only_own_qr_visits(): void
    {
        $receptionist = $this->data['receptionistA'];

        // Ekspor hanya lewat History; Tamu Aktif tidak memuat tombol ekspor.
        $this->actingAs($receptionist)->get('/admin/tamu')->assertOk()->assertDontSee('/admin/history/export', false);

        $this->actingAs($receptionist)->get('/admin/history/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.export', 'user_id' => $receptionist->id, 'detail->rows' => 3]);

        // Resepsionis tanpa QR tidak melihat kunjungan siapa pun.
        $other = $this->createUser(UserRole::Receptionist);
        $this->actingAs($other)->get('/admin/history')->assertOk()->assertDontSee('Tamu Selesai')->assertDontSee('Tamu Lokasi B');
        $this->actingAs($other)->get('/admin/history/export')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.export', 'user_id' => $other->id, 'detail->rows' => 0]);
    }

    public function test_pic_cannot_open_history_or_export(): void
    {
        $pic = $this->createPic($this->data['departmentA']);

        $this->actingAs($pic)->get('/admin/history')->assertForbidden();
        $this->actingAs($pic)->get('/admin/history/export')->assertForbidden();
    }

    public function test_status_filter_can_show_processing_visits(): void
    {
        $this->actingAs($this->data['admin'])->get('/admin/history?status=direview')
            ->assertSee('Tamu Direview')
            ->assertDontSee('Tamu Selesai');
    }

    public function test_search_and_filters(): void
    {
        $admin = $this->data['admin'];

        $this->actingAs($admin)->get('/admin/history?q=ekspor+kopi')->assertSee('Tamu Selesai')->assertDontSee('HYPERLINK');
        $this->actingAs($admin)->get('/admin/history?vulnerable_group=lansia')->assertSee('Tamu Selesai')->assertDontSee('HYPERLINK');
        $this->actingAs($admin)->get('/admin/history?department_id='.$this->data['departmentA']->id)->assertSee('Tamu Selesai')->assertDontSee('HYPERLINK');
        $this->actingAs($admin)->get('/admin/history?period=hari_ini')->assertSee('Tamu Selesai');
        $this->actingAs($admin)->get('/admin/history?from='.now()->addDay()->toDateString())->assertDontSee('Tamu Selesai');
    }

    public function test_admin_can_filter_any_location_but_unknown_ids_are_rejected(): void
    {
        $admin = $this->data['admin'];

        $this->actingAs($admin)->get('/admin/history?location_id='.$this->data['locationB']->id)
            ->assertOk()
            ->assertSee('Tamu Lokasi B')
            ->assertDontSee('Tamu Selesai');
        $this->actingAs($admin)->get('/admin/history?location_id=999999')->assertNotFound();
    }

    public function test_pdf_export_of_all_filtered_rows_is_guarded_and_audited(): void
    {
        $response = $this->actingAs($this->data['admin'])->get('/admin/history/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'no-store, private');

        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        // Tanda tangan disematkan sebagai gambar di PDF.
        $this->assertStringContainsString('/Subtype /Image', $pdf);
        $this->assertMatchesRegularExpression('/sitamu_semua_\d{8}-\d{4}\.pdf/', $response->headers->get('Content-Disposition'));
        // Semua kecuali Dibatalkan: Selesai A, Menunggu, Sedang direview, Selesai B.
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.export', 'user_id' => $this->data['admin']->id, 'detail->format' => 'pdf', 'detail->rows' => 4]);
    }

    public function test_pdf_table_rows_are_escaped_and_numbered(): void
    {
        $exports = app(ExportService::class);
        $visits = GuestVisit::query()->with('servedBy')->orderBy('id')->get();

        $html = view('admin.export.pdf', [
            'rows' => $visits->map(fn (GuestVisit $visit, int $index) => ['values' => $exports->row($visit, $index + 1), 'signature' => null]),
            'columns' => array_merge(ExportService::COLUMNS, ['TTD']),
            'widths' => ExportService::COLUMN_WIDTHS,
            'locationName' => 'Semua lokasi',
            'filters' => [],
            'rowCount' => $visits->count(),
        ])->render();

        $this->assertStringContainsString('=HYPERLINK(&quot;http://jahat.test&quot;)', $html);
        $this->assertStringNotContainsString('No. Urut', $html);
        $this->assertSame('1', $exports->row($visits->first(), 1)[0]);
        $this->assertStringContainsString('BUKU TAMU', $html);
    }

    public function test_export_uses_nine_guest_book_columns(): void
    {
        $this->assertSame(
            ['NO', 'NAMA', 'INSTANSI / PERUSAHAAN', 'BERTEMU', 'JAM', 'KEPERLUAN', 'NO. TELP', 'ALAMAT'],
            ExportService::COLUMNS,
        );
        $this->assertCount(count(ExportService::COLUMNS) + 1, ExportService::COLUMN_WIDTHS);

        $staff = $this->createStaffMember($this->data['departmentA']);
        $visit = GuestVisit::where('name', 'Tamu Selesai')->firstOrFail();
        GuestVisit::whereKey($visit->id)->update(['served_by_staff_id' => $staff->id, 'check_in_at' => '2026-11-12 08:15:00']);
        $visit = $visit->fresh('servedBy');

        $this->assertSame(
            ['3', 'Tamu Selesai', 'Pribadi', $staff->name, '12/11/2026 08:15', 'Konsultasi ekspor kopi', '081234567890', 'Jl. Ahmad Yani No. 1, Surabaya'],
            app(ExportService::class)->row($visit, 3),
        );
    }

    public function test_filter_summary_is_readable(): void
    {
        $summary = app(ExportService::class)->filterSummary([
            'from' => '2026-11-01', 'to' => '2026-11-12', 'status' => 'selesai',
            'department_id' => $this->data['departmentA']->id, 'vulnerable_group' => 'lansia', 'q' => 'kopi',
        ]);

        $this->assertSame([
            'Tanggal: 01/11/2026 – 12/11/2026', 'Status: Selesai', 'Bidang/UPT: Bidang A', 'Kelompok rentan: Lansia', 'Pencarian: "kopi"',
        ], $summary);
        $this->assertSame(['Periode: Hari ini'], app(ExportService::class)->filterSummary(['period' => 'hari_ini']));
    }

    public function test_history_page_offers_only_pdf_export(): void
    {
        $this->actingAs($this->data['admin'])->get('/admin/history')
            ->assertOk()
            ->assertSee('Ekspor PDF')
            ->assertDontSee('format=csv', false)
            ->assertDontSee('format=xlsx', false)
            ->assertDontSee('Excel');
    }

    public function test_pdf_export_is_generated(): void
    {
        $response = $this->actingAs($this->data['superAdmin'])
            ->get('/admin/history/export?format=pdf&location_id='.$this->data['locationA']->id)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('sitamu_kantor-a_', $response->headers->get('Content-Disposition'));
    }

    public function test_csv_excel_and_other_formats_are_rejected(): void
    {
        foreach (['csv', 'xlsx', 'docx'] as $format) {
            $this->actingAs($this->data['admin'])->from('/admin/history')->get("/admin/history/export?format={$format}")
                ->assertRedirect('/admin/history')
                ->assertSessionHasErrors(['format' => 'Ekspor hanya tersedia dalam format PDF.']);
        }

        $this->assertDatabaseMissing('audit_logs', ['action' => 'visit.export']);
    }

    public function test_receptionist_and_pic_cannot_open_history(): void
    {
        $this->actingAs($this->createUser(UserRole::Receptionist))->get('/admin/history')->assertForbidden();
        $this->flushSession();
        $this->actingAs($this->createPic($this->data['departmentA']))->get('/admin/history/export')->assertForbidden();
    }
}
