<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Membuat tabel visit_assignments.
     */
    public function up(): void
    {
        Schema::create('visit_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('guest_visits')->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at');
            $table->foreignId('staff_member_id')->constrained()->restrictOnDelete();
        });
    }

    /**
     * Menghapus tabel visit_assignments.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_assignments');
    }
};
