<?php

namespace Database\Seeders;

use App\Enums\VisitStatus;
use App\Enums\VulnerableGroup;
use App\Models\Department;
use App\Models\GuestVisit;
use App\Models\Location;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\VisitAssignment;
use App\Models\VisitStatusLog;
use Illuminate\Database\Seeder;

/**
 * Data kunjungan contoh untuk melihat tampilan di semua role
 * (Resepsionis, PIC, Admin, Super Admin) pada berbagai status.
 *
 * Tamu kelompok rentan (prioritas) diberi check_in paling awal agar
 * tampil di posisi paling atas daftar.
 */
class SampleVisitsSeeder extends Seeder
{
    /**
     * Membuat data kunjungan contoh.
     */
    public function run(): void
    {
        $location = Location::where('name', 'Kantor Dinas Jl. Ahmad Yani')->first();
        if (! $location) {
            $this->command?->warn('Lokasi contoh belum ada. Jalankan LocalSampleSeeder dulu.');

            return;
        }

        $qr = QrCode::where('location_id', $location->id)->first();
        $receptionist = User::where('email', 'resepsionis@sitamu.test')->first();
        $picSekretariat = User::where('email', 'pic.sekretariat@sitamu.test')->first();
        $categories = PurposeCategory::where('location_id', $location->id)->get()->keyBy('name');
        $departments = Department::where('location_id', $location->id)->get()->keyBy('name');
        $staff = StaffMember::query()->get()->keyBy('name');

        $visits = $this->visitDefinitions($location, $qr, $categories, $departments);

        foreach ($visits as $index => $definition) {
            $this->createVisit($definition, $index + 1, $receptionist, $picSekretariat, $staff);
        }

        $this->command?->info('Data kunjungan contoh dibuat: '.count($visits).' kunjungan.');
    }

    /**
     * Definisi seluruh kunjungan contoh. Urutan array menentukan check_in
     * (index kecil = check_in lebih awal = tampil lebih atas).
     *
     * @return array<int, array<string, mixed>>
     */
    private function visitDefinitions(Location $location, ?QrCode $qr, $categories, $departments): array
    {
        $now = now();

        return [
            // ============================================================
            // PRIORITAS (kelompok rentan) — check_in paling awal = paling atas
            // ============================================================
            [
                'name' => 'Hartono Wijaya',
                'organization' => 'Purna Tugas / Umum',
                'phone' => '081234560001',
                'address' => 'Jl. Kenanga No. 12, Surabaya',
                'email' => 'hartono.wijaya@example.com',
                'vulnerable_group' => VulnerableGroup::Elderly,
                'category' => 'Konsultasi perdagangan',
                'purpose' => 'Konsultasi izin usaha perdagangan untuk pensiunan.',
                'status' => VisitStatus::Waiting,
                'assigned_department' => null,
                'check_in_at' => $now->copy()->subMinutes(95),
            ],
            [
                'name' => 'Sari Melati',
                'organization' => 'CV Melati Mandiri',
                'phone' => '081234560002',
                'address' => 'Jl. Mawar No. 5, Sidoarjo',
                'email' => 'sari.melati@example.com',
                'vulnerable_group' => VulnerableGroup::Pregnant,
                'category' => 'Surat-menyurat',
                'purpose' => 'Pengurusan surat keterangan usaha.',
                'status' => VisitStatus::Waiting,
                'assigned_department' => 'Sekretariat',
                'check_in_at' => $now->copy()->subMinutes(88),
            ],
            [
                'name' => 'Bambang Riyanto',
                'organization' => 'UD Sinar Jaya',
                'phone' => '081234560003',
                'address' => 'Jl. Anggrek No. 21, Gresik',
                'email' => 'bambang.riyanto@example.com',
                'vulnerable_group' => VulnerableGroup::Disability,
                'category' => 'Konsultasi industri',
                'purpose' => 'Konsultasi pengembangan industri rumah tangga.',
                'status' => VisitStatus::Processing,
                'assigned_department' => 'Bidang Industri Agro',
                'check_in_at' => $now->copy()->subMinutes(80),
            ],

            // ============================================================
            // BUKAN PRIORITAS — berbagai status
            // ============================================================
            [
                'name' => 'Dedi Kurniawan',
                'organization' => 'PT Maju Bersama',
                'phone' => '081234560004',
                'address' => 'Jl. Basuki Rahmat No. 88, Surabaya',
                'email' => 'dedi.kurniawan@example.com',
                'vulnerable_group' => VulnerableGroup::None,
                'category' => 'Konsultasi perdagangan',
                'purpose' => 'Konsultasi ekspor produk kerajinan.',
                'status' => VisitStatus::Waiting,
                'assigned_department' => 'Bidang Perdagangan Dalam Negeri',
                'check_in_at' => $now->copy()->subMinutes(60),
            ],
            [
                'name' => 'Laila Fitriani',
                'organization' => 'Koperasi Sejahtera',
                'phone' => '081234560005',
                'address' => 'Jl. Diponegoro No. 45, Surabaya',
                'email' => 'laila.fitriani@example.com',
                'vulnerable_group' => VulnerableGroup::None,
                'category' => 'Surat-menyurat',
                'purpose' => 'Penyerahan proposal kerja sama koperasi.',
                'status' => VisitStatus::Processing,
                'assigned_department' => 'Sekretariat',
                'check_in_at' => $now->copy()->subMinutes(45),
            ],
            [
                'name' => 'Rudi Hartanto',
                'organization' => 'BUMDes Makmur',
                'phone' => '081234560006',
                'address' => 'Jl. Raya Darmo No. 3, Surabaya',
                'email' => 'rudi.hartanto@example.com',
                'vulnerable_group' => VulnerableGroup::None,
                'category' => 'Lainnya',
                'purpose' => 'Koordinasi program pemberdayaan desa.',
                'status' => VisitStatus::Completed,
                'assigned_department' => 'Sekretariat',
                'check_in_at' => $now->copy()->subMinutes(180),
                'result' => 'Koordinasi selesai, tindak lanjut via email.',
            ],
            [
                'name' => 'Nina Kurniasih',
                'organization' => 'Perorangan',
                'phone' => '081234560007',
                'address' => 'Jl. Pahlawan No. 7, Mojokerto',
                'email' => 'nina.kurniasih@example.com',
                'vulnerable_group' => VulnerableGroup::None,
                'category' => 'Konsultasi industri',
                'purpose' => 'Pendaftaran program pelatihan industri.',
                'status' => VisitStatus::Cancelled,
                'assigned_department' => null,
                'check_in_at' => $now->copy()->subMinutes(150),
                'cancel_reason' => 'Tamu berhalangan hadir, dibatalkan resepsionis.',
            ],
            [
                'name' => 'Joko Susilo',
                'organization' => 'CV Abadi Sentosa',
                'phone' => '081234560008',
                'address' => 'Jl. Gubernur Suryo No. 15, Surabaya',
                'email' => 'joko.susilo@example.com',
                'vulnerable_group' => VulnerableGroup::Undisclosed,
                'category' => 'Konsultasi perdagangan',
                'purpose' => 'Konsultasi sertifikasi produk perdagangan.',
                'status' => VisitStatus::Waiting,
                'assigned_department' => null,
                'check_in_at' => $now->copy()->subMinutes(20),
            ],
        ];
    }

    /**
     * Membuat satu kunjungan beserta relasi penugasan dan log statusnya.
     *
     * @param  array<string, mixed>  $data
     */
    private function createVisit(array $data, int $dailyNumber, ?User $receptionist, ?User $pic, $staff): void
    {
        $location = Location::where('name', 'Kantor Dinas Jl. Ahmad Yani')->first();
        $qr = QrCode::where('location_id', $location->id)->first();
        $category = PurposeCategory::where('location_id', $location->id)
            ->where('name', $data['category'])->first();
        $assignedDept = $data['assigned_department']
            ? Department::where('location_id', $location->id)->where('name', $data['assigned_department'])->first()
            : null;

        $visit = GuestVisit::updateOrCreate(
            ['email' => $data['email'], 'visit_date' => now()->toDateString()],
            [
                'location_id' => $location->id,
                'qr_code_id' => $qr?->id,
                'visit_date' => now()->toDateString(),
                'daily_number' => $dailyNumber,
                'email' => $data['email'],
                'email_verified_at' => $data['check_in_at'],
                'purpose_category_id' => $category?->id,
                'purpose_category_name' => $data['category'],
                'suggested_department_id' => $category?->suggested_department_id,
                'purpose' => $data['purpose'],
                'vulnerable_group' => $data['vulnerable_group'],
                'name' => $data['name'],
                'organization' => $data['organization'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'status' => $data['status'],
                'check_in_at' => $data['check_in_at'],
                'assigned_department_id' => $assignedDept?->id,
                'assigned_at' => $assignedDept ? $data['check_in_at']->copy()->addMinutes(3) : null,
                'assigned_by' => $assignedDept && $receptionist ? $receptionist->id : null,
                'processed_at' => $data['status'] === VisitStatus::Processing || $data['status'] === VisitStatus::Completed
                    ? $data['check_in_at']->copy()->addMinutes(8) : null,
                'processed_by' => ($data['status'] === VisitStatus::Processing || $data['status'] === VisitStatus::Completed) && $pic ? $pic->id : null,
                'result' => $data['result'] ?? null,
                'completed_at' => $data['status'] === VisitStatus::Completed ? $data['check_in_at']->copy()->addMinutes(30) : null,
                'cancelled_at' => $data['status'] === VisitStatus::Cancelled ? $data['check_in_at']->copy()->addMinutes(10) : null,
                'cancel_reason' => $data['cancel_reason'] ?? null,
                'closed_by' => in_array($data['status'], [VisitStatus::Completed, VisitStatus::Cancelled]) && $pic ? $pic->id : null,
                'consent_at' => $data['check_in_at'],
                'ip_address' => '127.0.0.1',
            ]
        );
    }
}
