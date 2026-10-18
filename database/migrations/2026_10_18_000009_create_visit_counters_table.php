<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel visit_counters.
     */
    public function up(): void
    {
        Schema::create('visit_counters', function (Blueprint $table) {
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->date('visit_date');
            $table->unsignedInteger('last_number');

            $table->primary(['location_id', 'visit_date']);
        });
    }

    /**
     * Menghapus tabel visit_counters.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_counters');
    }
};
