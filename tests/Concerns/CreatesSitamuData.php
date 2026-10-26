<?php

namespace Tests\Concerns;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\GuestVisit;
use App\Models\Location;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\StaffMember;
use App\Models\User;

/**
 * Pembuat data uji SiTamu yang dipakai bersama oleh beberapa tes.
 */
trait CreatesSitamuData
{
    protected function createLocation(?string $name = null): Location
    {
        return Location::create(['name' => $name ?? 'Lokasi '.fake()->unique()->numerify('###')]);
    }

    protected function createDepartment(Location $location, ?string $name = null): Department
    {
        return Department::create(['location_id' => $location->id, 'name' => $name ?? 'Bidang '.fake()->unique()->numerify('###')]);
    }

    protected function createUser(UserRole $role, array $attributes = []): User
    {
        return User::factory()->role($role)->create($attributes);
    }

    protected function createAdmin(): User
    {
        return $this->createUser(UserRole::Admin);
    }

    protected function createPic(Department $department): User
    {
        return $this->createUser(UserRole::Pic, ['department_id' => $department->id]);
    }

    protected function createQr(Location $location, User $receptionist, QrStatus $status = QrStatus::Active): QrCode
    {
        return QrCode::create([
            'location_id' => $location->id,
            'receptionist_id' => $receptionist->id,
            'token' => bin2hex(random_bytes(32)),
            'status' => $status,
        ]);
    }

    protected function createCategory(Location $location, ?Department $suggested = null): PurposeCategory
    {
        return PurposeCategory::create([
            'location_id' => $location->id,
            'name' => 'Kategori '.fake()->unique()->numerify('###'),
            'suggested_department_id' => $suggested?->id,
        ]);
    }

    protected function createStaffMember(Department $department): StaffMember
    {
        return StaffMember::create(['department_id' => $department->id, 'name' => fake()->unique()->name()]);
    }

    /**
     * Membuat kunjungan berstatus waiting lewat QR tertentu.
     */
    protected function createVisit(QrCode $qr, PurposeCategory $category, array $attributes = []): GuestVisit
    {
        // Nomor besar agar tidak bentrok dengan nomor dari visit_counters saat formulir dikirim di tes yang sama.
        static $dailyNumber = 10000;

        $visit = GuestVisit::create(array_merge([
            'location_id' => $qr->location_id,
            'qr_code_id' => $qr->id,
            'visit_date' => now()->toDateString(),
            'daily_number' => ++$dailyNumber,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'purpose_category_id' => $category->id,
            'purpose_category_name' => $category->name,
            'suggested_department_id' => $category->suggested_department_id,
            'purpose' => 'Konsultasi',
            'vulnerable_group' => 'bukan',
            'name' => fake()->name(),
            'organization' => 'Pribadi',
            'phone' => '6281234567890',
            'address' => 'Jl. Ahmad Yani No. 1, Surabaya',
            'status' => 'waiting',
            'check_in_at' => now(),
            'consent_at' => now(),
        ], array_intersect_key($attributes, array_flip((new GuestVisit())->getFillable()))));

        $extra = array_diff_key($attributes, array_flip($visit->getFillable()));

        if ($extra !== []) {
            GuestVisit::whereKey($visit->id)->update($extra);
        }

        return $visit->refresh();
    }
}
