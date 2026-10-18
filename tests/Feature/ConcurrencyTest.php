<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Models\GuestVisit;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji aksi yang benar-benar bersamaan lewat beberapa proses PHP dengan koneksi
 * MySQL masing-masing (DoD M3 dan M4, Bab 7.2, Bab 9.3).
 *
 * Proses anak tidak dapat melihat data di dalam transaksi tes, jadi kelas ini tidak
 * memakai RefreshDatabase: data disimpan sungguhan lalu tabel dikosongkan setelah tiap tes.
 */
class ConcurrencyTest extends TestCase
{
    use CreatesSitamuData;

    /**
     * Tabel yang diisi tes ini, dikosongkan setelah tiap tes.
     */
    private const TABLES = [
        'visit_status_logs', 'visit_signatures', 'visit_assignments', 'guest_visits', 'visit_counters',
        'audit_logs', 'staff_members', 'purpose_categories', 'qr_codes', 'user_locations', 'users',
        'departments', 'locations',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Tes ini mengosongkan tabel; tolak berjalan bila database bukan database khusus tes.
        if (! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
            $this->markTestSkipped('ConcurrencyTest hanya berjalan pada database bernama *_test.');
        }

        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh');
            RefreshDatabaseState::$migrated = true;
        }

        $this->truncateTables();
    }

    protected function tearDown(): void
    {
        $this->truncateTables();

        parent::tearDown();
    }

    public function test_simultaneous_submissions_get_unique_sequential_numbers(): void
    {
        $location = $this->createLocation();
        $qr = $this->createQr($location, $this->createUser(UserRole::Receptionist));
        $category = $this->createCategory($location);
        $workers = 10;

        $results = $this->runTogether($workers, fn (int $index) => [
            'record', (string) $qr->id, (string) $category->id, "paralel{$index}@contoh.test",
        ]);

        $numbers = array_map('intval', $results);
        sort($numbers);

        $this->assertSame(range(1, $workers), $numbers, 'Setiap kiriman harus mendapat nomor unik tanpa celah.');
        $this->assertSame($workers, GuestVisit::count());
        $this->assertSame($workers, DB::table('visit_signatures')->count());
        $this->assertSame($workers, (int) DB::table('visit_counters')->value('last_number'));
    }

    public function test_same_assignment_cannot_be_accepted_twice_at_the_same_time(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location);
        $staff = $this->createStaffMember($department);
        $pic = $this->createPic($department);
        $qr = $this->createQr($location, $this->createUser(UserRole::Receptionist));
        $visit = $this->createVisit($qr, $this->createCategory($location), [
            'assigned_department_id' => $department->id,
            'served_by_staff_id' => $staff->id,
        ]);

        // Satu akun PIC dipakai beberapa orang; dua orang menekan Terima bersamaan.
        $results = $this->runTogether(2, fn () => ['accept', (string) $visit->id, (string) $pic->id]);

        sort($results);
        $this->assertSame(['conflict', 'ok'], $results);
        $this->assertSame(VisitStatus::Processing, $visit->refresh()->status);
        $this->assertSame(1, DB::table('visit_status_logs')->where('visit_id', $visit->id)->where('to_status', 'processing')->count());
    }

    /**
     * Menjalankan beberapa proses pekerja yang mulai pada detik yang sama dan
     * mengembalikan keluaran masing-masing.
     *
     * @param  callable(int): array<int, string>  $arguments
     * @return array<int, string>
     */
    private function runTogether(int $count, callable $arguments): array
    {
        // Jeda cukup lama agar semua proses selesai boot sebelum waktu mulai.
        $startAt = (string) (microtime(true) + 4);
        $env = $this->workerEnvironment();

        $results = Process::pool(function (Pool $pool) use ($count, $arguments, $startAt, $env) {
            for ($index = 0; $index < $count; $index++) {
                $pool->path(base_path())->env($env)->timeout(60)->command(array_merge(
                    [PHP_BINARY, 'tests/Support/concurrent-worker.php'],
                    [$arguments($index)[0], $startAt],
                    array_slice($arguments($index), 1),
                ));
            }
        })->start()->wait();

        return $results->collect()->map(function ($result) {
            $this->assertTrue($result->successful(), 'Proses pekerja gagal: '.$result->errorOutput());

            return trim($result->output());
        })->values()->all();
    }

    /**
     * Proses anak memakai database yang sama persis dengan tes ini.
     *
     * @return array<string, string>
     */
    private function workerEnvironment(): array
    {
        $connection = config('database.connections.mysql');

        return [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) $connection['host'],
            'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => (string) $connection['database'],
            'DB_USERNAME' => (string) $connection['username'],
            'DB_PASSWORD' => (string) $connection['password'],
        ];
    }

    /**
     * Mengosongkan tabel tanpa lewat Eloquent, karena beberapa model menolak penghapusan.
     */
    private function truncateTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
