<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\GuestVisit;
use App\Models\Location;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * Memastikan aturan model dan skema penting dari PRD Bab 9.2.
 */
class ModelRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_cannot_be_deleted(): void
    {
        $department = Department::create(['location_id' => Location::create(['name' => 'Kantor A'])->id, 'name' => 'Bidang A']);

        $this->expectException(LogicException::class);

        $department->delete();
    }

    public function test_audit_log_cannot_be_updated(): void
    {
        $log = AuditLog::create(['action' => 'auth.login_success']);

        $this->expectException(LogicException::class);

        $log->update(['action' => 'auth.logout']);
    }

    public function test_waiting_visit_shows_menunggu_pic_after_assignment(): void
    {
        $visit = $this->createVisit();

        $this->assertSame(['label' => 'Menunggu', 'badge' => 'warning'], $visit->display_status);

        $visit->assigned_department_id = $visit->suggested_department_id;

        $this->assertSame(['label' => 'Menunggu PIC', 'badge' => 'info'], $visit->display_status);
    }

    public function test_signature_column_is_mediumblob(): void
    {
        $type = DB::selectOne(
            'SELECT DATA_TYPE AS type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['visit_signatures', 'image'],
        )->type;

        $this->assertSame('mediumblob', $type);
    }

    /**
     * Membuat satu kunjungan berstatus waiting beserta data induknya.
     */
    private function createVisit(): GuestVisit
    {
        $location = Location::create(['name' => 'Kantor A']);
        $department = Department::create(['location_id' => $location->id, 'name' => 'Bidang A']);
        $receptionist = User::factory()->role(UserRole::Receptionist)->create();
        $qr = QrCode::create(['location_id' => $location->id, 'receptionist_id' => $receptionist->id, 'token' => bin2hex(random_bytes(32))]);
        $category = PurposeCategory::create(['location_id' => $location->id, 'name' => 'Konsultasi', 'suggested_department_id' => $department->id]);

        return GuestVisit::create([
            'location_id' => $location->id,
            'qr_code_id' => $qr->id,
            'visit_date' => now()->toDateString(),
            'daily_number' => 1,
            'email' => 'tamu@contoh.test',
            'email_verified_at' => now(),
            'purpose_category_id' => $category->id,
            'purpose_category_name' => $category->name,
            'suggested_department_id' => $department->id,
            'purpose' => 'Konsultasi perizinan',
            'vulnerable_group' => 'bukan',
            'name' => 'Tamu Uji',
            'organization' => 'Pribadi',
            'phone' => '6281234567890',
            'address' => 'Jl. Ahmad Yani No. 1, Surabaya',
            'status' => 'waiting',
            'check_in_at' => now(),
            'consent_at' => now(),
        ])->refresh();
    }
}
