<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel visit_status_logs.
     */
    public function up(): void
    {
        Schema::create('visit_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('guest_visits')->restrictOnDelete();
            $table->unsignedBigInteger('location_id');
            $table->enum('from_status', ['waiting', 'processing', 'completed', 'cancelled'])->nullable();
            $table->enum('to_status', ['waiting', 'processing', 'completed', 'cancelled']);
            // NULL berarti perubahan dibuat oleh tamu.
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_member_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('note', 2500)->nullable();
            $table->dateTime('created_at');
        });
    }

    /**
     * Menghapus tabel visit_status_logs.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_status_logs');
    }
};
