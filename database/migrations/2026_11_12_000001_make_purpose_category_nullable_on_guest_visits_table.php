<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Kunjungan baru tidak lagi memilih kategori keperluan (revisi HRD #6 dan #7), sehingga
     * kedua kolom boleh kosong. Kategori kunjungan lama tetap tersimpan.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE guest_visits MODIFY purpose_category_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE guest_visits MODIFY purpose_category_name VARCHAR(150) NULL');
    }

    /**
     * Mengembalikan kolom menjadi wajib. Gagal bila sudah ada kunjungan tanpa kategori.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE guest_visits MODIFY purpose_category_name VARCHAR(150) NOT NULL');
        DB::statement('ALTER TABLE guest_visits MODIFY purpose_category_id BIGINT UNSIGNED NOT NULL');
    }
};
