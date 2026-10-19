<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Memperlebar guest_visits.purpose_category_name menjadi 150 karakter agar muat kalimat pertanyaan (KAT-1).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE guest_visits MODIFY purpose_category_name VARCHAR(150) NOT NULL');
    }

    /**
     * Mengembalikan guest_visits.purpose_category_name menjadi 100 karakter.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE guest_visits MODIFY purpose_category_name VARCHAR(100) NOT NULL');
    }
};
