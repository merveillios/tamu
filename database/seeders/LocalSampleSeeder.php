<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Location;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh untuk pengembangan lokal (A-10). Password akun dibuat acak dan
 * hanya ditampilkan di konsol, tidak pernah ditulis di kode.
 */
class LocalSampleSeeder extends Seeder
{
    /**
     * Struktur lokasi → departemen → petugas contoh.
     */
    private const LOCATIONS = [
        'Kantor Dinas Jl. Ahmad Yani' => [
            'Bidang Perdagangan Dalam Negeri' => ['Budi Santoso', 'Rina Wulandari'],
            'Bidang Industri Agro' => ['Agus Prasetyo', 'Dewi Lestari'],
            'Sekretariat' => ['Siti Aminah'],
        ],
        'UPT Pengujian Mutu Barang' => [
            'Seksi Pelayanan Teknis' => ['Hendra Gunawan'],
            'Tata Usaha' => ['Lina Marlina'],
        ],
    ];

    /**
     * Kategori keperluan lokasi pertama beserta saran departemennya.
     */
    private const CATEGORIES = [
        'Konsultasi perdagangan' => 'Bidang Perdagangan Dalam Negeri',
        'Konsultasi industri' => 'Bidang Industri Agro',
        'Surat-menyurat' => 'Sekretariat',
        'Lainnya' => null,
    ];

    /**
     * Mengisi data contoh lokal.
     */
    public function run(): void
    {
        $departments = $this->seedLocations();
        $headquarters = Location::where('name', array_key_first(self::LOCATIONS))->firstOrFail();

        $this->seedAccount('Super Admin Contoh', 'superadmin@sitamu.test', UserRole::SuperAdmin);
        $admin = $this->seedAccount('Admin Kantor Dinas', 'admin@sitamu.test', UserRole::Admin);
        $admin->locations()->syncWithoutDetaching([$headquarters->id => ['assigned_at' => now()]]);

        $receptionist = $this->seedAccount('Resepsionis Pintu Timur', 'resepsionis@sitamu.test', UserRole::Receptionist);
        QrCode::firstOrCreate(
            ['location_id' => $headquarters->id, 'label' => 'Pintu Timur'],
            [
                'department_id' => $departments['Sekretariat']->id,
                'receptionist_id' => $receptionist->id,
                'token' => bin2hex(random_bytes(32)),
                'status' => 'active',
            ],
        );

        foreach ($departments as $department) {
            $email = 'pic.'.Str::slug($department->name, '.').'@sitamu.test';
            $this->seedAccount('PIC '.$department->name, $email, UserRole::Pic, $department->id);
        }

        $this->seedAccount('PIC Contoh', 'pic@sitamu.test', UserRole::Pic, $departments['Sekretariat']->id);

        foreach (self::CATEGORIES as $name => $departmentName) {
            PurposeCategory::firstOrCreate(
                ['location_id' => $headquarters->id, 'name' => $name],
                ['suggested_department_id' => $departmentName ? $departments[$departmentName]->id : null],
            );
        }
    }

    /**
     * Membuat lokasi, departemen, dan petugas contoh.
     *
     * @return array<string, Department>
     */
    private function seedLocations(): array
    {
        $departments = [];

        foreach (self::LOCATIONS as $locationName => $departmentList) {
            $location = Location::firstOrCreate(['name' => $locationName]);

            foreach ($departmentList as $departmentName => $staffNames) {
                $department = Department::firstOrCreate(['location_id' => $location->id, 'name' => $departmentName]);
                $departments[$departmentName] = $department;

                foreach ($staffNames as $staffName) {
                    StaffMember::firstOrCreate(['department_id' => $department->id, 'name' => $staffName]);
                }
            }
        }

        return $departments;
    }

    /**
     * Membuat akun contoh bila belum ada dan menampilkan password acaknya.
     */
    private function seedAccount(string $name, string $email, UserRole $role, ?int $departmentId = null): User
    {
        $password = env('SAMPLE_PASSWORD', 'password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => $role,
                'department_id' => $departmentId,
                'is_active' => true,
                'failed_logins' => 0,
                'locked_until' => null,
            ]
        );

        $this->command?->line(sprintf('  %-50s %s', $email, $password));

        return $user;
    }
}
