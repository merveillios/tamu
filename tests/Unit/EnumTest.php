<?php

namespace Tests\Unit;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Enums\VulnerableGroup;
use PHPUnit\Framework\TestCase;

/**
 * Memastikan pemetaan enum sesuai PRD Bab 0.4 dan Bab 11.1.
 */
class EnumTest extends TestCase
{
    public function test_visit_status_label_and_badge_follow_status_mapping(): void
    {
        $this->assertSame(['Menunggu', 'warning'], [VisitStatus::Waiting->label(), VisitStatus::Waiting->badge()]);
        $this->assertSame(['Sedang direview', 'primary'], [VisitStatus::Processing->label(), VisitStatus::Processing->badge()]);
        $this->assertSame(['Selesai', 'success'], [VisitStatus::Completed->label(), VisitStatus::Completed->badge()]);
        $this->assertSame(['Dibatalkan', 'secondary'], [VisitStatus::Cancelled->label(), VisitStatus::Cancelled->badge()]);
    }

    public function test_only_waiting_and_processing_are_active(): void
    {
        $this->assertTrue(VisitStatus::Waiting->isActive());
        $this->assertTrue(VisitStatus::Processing->isActive());
        $this->assertFalse(VisitStatus::Completed->isActive());
        $this->assertFalse(VisitStatus::Cancelled->isActive());
    }

    public function test_enum_values_match_database_values(): void
    {
        $this->assertSame(['super_admin', 'admin', 'receptionist', 'pic'], array_column(UserRole::cases(), 'value'));
        $this->assertSame(['active', 'inactive', 'revoked'], array_column(QrStatus::cases(), 'value'));
        $this->assertSame(
            ['bukan', 'lansia', 'disabilitas', 'ibu_hamil', 'tidak_menyebutkan'],
            array_column(VulnerableGroup::cases(), 'value'),
        );
    }
}
