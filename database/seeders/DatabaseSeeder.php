<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data contoh hanya diisi di lingkungan lokal; produksi tidak pernah diberi data dummy (A-10).
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Seeder data contoh hanya berjalan jika APP_ENV=local.');

            return;
        }

        $this->command?->info('Akun contoh baru (catat password ini, tidak ditampilkan lagi):');
        $this->call(LocalSampleSeeder::class);
        $this->call(SampleVisitsSeeder::class);
    }
}
