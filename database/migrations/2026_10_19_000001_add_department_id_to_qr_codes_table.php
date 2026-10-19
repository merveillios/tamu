<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Menambah departemen tujuan QR. Kolom boleh kosong karena QR lama belum
     * memiliki departemen; QR baru wajib diisi lewat validasi form (QR-2).
     */
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('location_id')->constrained()->restrictOnDelete();
        });
    }

    /**
     * Menghapus kolom departemen QR.
     */
    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
