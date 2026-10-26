<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\VisitSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji History Kunjungan dan ekspor CSV, Excel, PDF (HIS-1 sampai HIS-5, Bab 12.4).
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
        $qrA = $this->createQr($locationA, $this->createUser(UserRole::Receptionist));
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

        $this->data = compact('locationA', 'locationB', 'categoryB', 'departmentA') + [
            'admin' => $this->createAdmin(),
            'superAdmin' => $this->createUser(UserRole::SuperAdmin),
        ];
    }

    public function test_history_shows_completed_and_cancelled_of_all_locations_by_default(): void
    {
        $this->actingAs($this->data['admin'])->get('/admin/history')
            ->assertOk()
            ->assertSee('Tamu Selesai')
            ->assertSee('HYPERLINK')
            ->assertDontSee('Tamu Menunggu')
            ->assertDontSee('Tamu Direview')
            ->assertSee('Tamu Lokasi B');
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
        $this->actingAs($admin)->get('/admin/history?purpose_category_id='.$this->data['categoryB']->id)->assertOk();
        $this->actingAs($admin)->get('/admin/history?location_id=999999')->assertNotFound();
        $this->actingAs($admin)->get('/admin/history?purpose_category_id=999999')->assertNotFound();
    }

    public function test_csv_export_is_guarded_and_audited(): void
    {
        $response = $this->actingAs($this->data['admin'])->get('/admin/history/export?format=csv')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Cache-Control', 'no-store, private');

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('"No. Urut";"Waktu Datang";Lokasi', $csv);
        $this->assertStringContainsString('Tamu Selesai', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('Tamu Lokasi B', $csv);
        $this->assertStringNotContainsString('Tamu Menunggu', $csv);
        $this->assertMatchesRegularExpression('/sitamu_semua_\d{8}-\d{4}\.csv/', $response->headers->get('Content-Disposition'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.export', 'user_id' => $this->data['admin']->id, 'detail->format' => 'csv', 'detail->rows' => 3]);
    }

    public function test_xlsx_export_writes_strings_and_signature_images(): void
    {
        $response = $this->actingAs($this->data['admin'])->get('/admin/history/export?format=xlsx')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);

        $this->assertSame('Tanda Tangan', $sheet->getCell('P1')->getValue());
        $names = [$sheet->getCell('D2')->getValue(), $sheet->getCell('D3')->getValue(), $sheet->getCell('D4')->getValue()];
        $this->assertContains('=HYPERLINK("http://jahat.test")', $names);
        $this->assertSame('s', $sheet->getCell('D'.(array_search('=HYPERLINK("http://jahat.test")', $names) + 2))->getDataType());
        $this->assertCount(1, $sheet->getDrawingCollection());
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

    public function test_rich_exports_over_limit_are_rejected_but_csv_is_not(): void
    {
        config(['sitamu.export.rich_max_rows' => 1]);
        $admin = $this->data['admin'];

        $this->actingAs($admin)->from('/admin/history')->get('/admin/history/export?format=xlsx')
            ->assertRedirect('/admin/history')
            ->assertSessionHasErrors(['format' => 'Hasil filter 3 baris, melebihi batas 1 baris. Persempit filter atau gunakan ekspor CSV.']);
        $this->actingAs($admin)->get('/admin/history/export?format=pdf')->assertSessionHasErrors('format');
        $this->actingAs($admin)->get('/admin/history/export?format=csv')->assertOk();
    }

    public function test_invalid_format_is_rejected(): void
    {
        $this->actingAs($this->data['admin'])->get('/admin/history/export?format=docx')->assertSessionHasErrors('format');
    }

    public function test_receptionist_and_pic_cannot_open_history(): void
    {
        $this->actingAs($this->createUser(UserRole::Receptionist))->get('/admin/history')->assertForbidden();
        $this->flushSession();
        $this->actingAs($this->createPic($this->data['departmentA']))->get('/admin/history/export')->assertForbidden();
    }
}
