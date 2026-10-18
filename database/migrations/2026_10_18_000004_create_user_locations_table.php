<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel user_locations.
     */
    public function up(): void
    {
        Schema::create('user_locations', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at');

            $table->primary(['user_id', 'location_id']);
        });
    }

    /**
     * Menghapus tabel user_locations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
};
