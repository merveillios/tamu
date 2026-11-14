<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Kunjungan yang diinput resepsionis untuk tamu tanpa login Google (RSP-11, revisi HRD #11).
     *
     * - guest_visits.entered_by: akun resepsionis yang menginput; kosong = diisi tamu lewat Google.
     * - guest_visits.email_verified_at boleh kosong karena email manual tidak terverifikasi.
     * - guest_documents.uploaded_by: akun petugas yang mengambil foto; kosong = diunggah tamu.
     */
    public function up(): void
    {
        Schema::table('guest_visits', function (Blueprint $table) {
            $table->foreignId('entered_by')->nullable()->after('ip_address')->constrained('users')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE guest_visits MODIFY email_verified_at DATETIME NULL');

        Schema::table('guest_documents', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->after('ip_address')->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Mengembalikan skema. Gagal bila sudah ada kunjungan manual tanpa email_verified_at.
     */
    public function down(): void
    {
        Schema::table('guest_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
        });

        DB::statement('ALTER TABLE guest_visits MODIFY email_verified_at DATETIME NOT NULL');

        Schema::table('guest_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entered_by');
        });
    }
};
