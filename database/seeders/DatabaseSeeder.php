<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Super Admin pertama dari .env berjalan di semua lingkungan dan hanya sekali.
     * Data contoh hanya diisi di lingkungan lokal; produksi tidak pernah diberi data dummy (A-10).
     */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);

        if (! app()->environment('local')) {
            $this->command?->warn('Seeder data contoh hanya berjalan jika APP_ENV=local.');

            return;
        }

        $this->command?->info('Akun contoh baru (catat password ini, tidak ditampilkan lagi):');
        $this->call(LocalSampleSeeder::class);
        $this->call(SampleVisitsSeeder::class);
    }
}
