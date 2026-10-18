<?php

namespace Tests\Unit;

use App\Rules\IndonesianPhone;
use PHPUnit\Framework\TestCase;

/**
 * Menguji normalisasi nomor telepon Indonesia (Bab 11.1).
 */
class IndonesianPhoneTest extends TestCase
{
    public function test_accepted_formats_are_normalized_to_62(): void
    {
        $this->assertSame('6281234567890', IndonesianPhone::normalize('081234567890'));
        $this->assertSame('6281234567890', IndonesianPhone::normalize('6281234567890'));
        $this->assertSame('6281234567890', IndonesianPhone::normalize('+62 812-3456-7890'));
        $this->assertSame('62315551234', IndonesianPhone::normalize('(031) 555-1234'));
    }

    public function test_invalid_numbers_are_rejected(): void
    {
        $this->assertNull(IndonesianPhone::normalize('12345'));
        $this->assertNull(IndonesianPhone::normalize('0812abc'));
        $this->assertNull(IndonesianPhone::normalize('+1 555 123 4567'));
        $this->assertNull(IndonesianPhone::normalize('0812345678901234567'));
    }

    public function test_display_uses_leading_zero(): void
    {
        $this->assertSame('081234567890', IndonesianPhone::display('6281234567890'));
    }
}
