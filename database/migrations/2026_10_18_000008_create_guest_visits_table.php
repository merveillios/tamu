<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Membuat tabel guest_visits.
     */
    public function up(): void
    {
        Schema::create('guest_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('qr_code_id')->constrained()->restrictOnDelete();
            $table->date('visit_date');
            $table->unsignedInteger('daily_number');
            $table->string('email', 254);
            $table->dateTime('email_verified_at');
            $table->foreignId('purpose_category_id')->constrained()->restrictOnDelete();
            // Salinan saat kunjungan dibuat agar History tetap akurat bila kategori diubah.
            $table->string('purpose_category_name', 100);
            $table->foreignId('suggested_department_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->string('purpose', 500);
            $table->string('vulnerable_group', 30);
            $table->string('name', 100);
            $table->string('organization', 150);
            $table->string('phone', 20);
            $table->string('address', 255);
            $table->enum('status', ['waiting', 'processing', 'completed', 'cancelled'])->default('waiting');
            $table->dateTime('check_in_at');
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('served_by_staff_id')->nullable()->constrained('staff_members')->restrictOnDelete();
            $table->text('result')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('consent_at');
            $table->string('ip_address', 45)->nullable();

            $table->unique(['location_id', 'visit_date', 'daily_number']);
            $table->index(['location_id', 'status', 'check_in_at']);
            $table->index(['qr_code_id', 'status', 'check_in_at']);
            $table->index(['assigned_department_id', 'status', 'check_in_at']);
            $table->index(['email', 'check_in_at']);
            $table->index(['status', 'check_in_at']);
            $table->index('check_in_at');
            $table->index('completed_at');
        });
    }

    /**
     * Menghapus tabel guest_visits.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_visits');
    }
};
