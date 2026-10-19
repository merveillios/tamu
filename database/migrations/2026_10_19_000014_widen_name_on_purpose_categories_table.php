<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Memperlebar purpose_categories.name menjadi 150 karakter agar muat kalimat pertanyaan (KAT-1).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE purpose_categories MODIFY name VARCHAR(150) NOT NULL');
    }

    /**
     * Mengembalikan purpose_categories.name menjadi 100 karakter.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE purpose_categories MODIFY name VARCHAR(100) NOT NULL');
    }
};
