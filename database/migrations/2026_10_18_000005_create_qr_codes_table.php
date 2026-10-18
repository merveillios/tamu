<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel qr_codes.
     */
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('receptionist_id')->constrained('users')->restrictOnDelete();
            $table->char('token', 64)->unique();
            $table->string('label', 100)->nullable();
            $table->enum('status', ['active', 'inactive', 'revoked'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at');
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
        });
    }

    /**
     * Menghapus tabel qr_codes.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
