<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Pilihan bawaan yang dulu ditulis di enum VulnerableGroup. Kodenya sama dengan nilai
     * guest_visits.vulnerable_group, jadi kunjungan lama tidak perlu diubah (revisi HRD #12).
     *
     * @var array<int, array{code: string, name: string, priority: ?int}>
     */
    private const DEFAULTS = [
        ['code' => 'bukan', 'name' => 'Bukan kelompok rentan', 'priority' => null],
        ['code' => 'lansia', 'name' => 'Lansia', 'priority' => 1],
        ['code' => 'disabilitas', 'name' => 'Penyandang disabilitas', 'priority' => 2],
        ['code' => 'ibu_hamil', 'name' => 'Ibu hamil', 'priority' => 3],
        ['code' => 'tidak_menyebutkan', 'name' => 'Tidak ingin menyebutkan', 'priority' => null],
    ];

    /**
     * Membuat tabel vulnerable_groups dan mengisinya dengan pilihan bawaan.
     */
    public function up(): void
    {
        Schema::create('vulnerable_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100)->unique();
            $table->unsignedTinyInteger('priority')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        $now = now();
        $rows = [];

        foreach (self::DEFAULTS as $index => $group) {
            $rows[] = $group + ['sort_order' => ($index + 1) * 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
        }

        // Jaga-jaga: kode lain yang sudah tersimpan di kunjungan ikut dibuat (nonaktif) agar tetap punya label.
        $known = array_column(self::DEFAULTS, 'code');
        $unknown = DB::table('guest_visits')->whereNotIn('vulnerable_group', $known)->distinct()->pluck('vulnerable_group');

        foreach ($unknown as $code) {
            $rows[] = [
                'code' => $code, 'name' => $code, 'priority' => null, 'sort_order' => 999,
                'is_active' => false, 'created_at' => $now, 'updated_at' => $now,
            ];
        }

        DB::table('vulnerable_groups')->insert($rows);
    }

    /**
     * Menghapus tabel vulnerable_groups. Kunjungan tetap menyimpan kodenya.
     */
    public function down(): void
    {
        Schema::dropIfExists('vulnerable_groups');
    }
};
