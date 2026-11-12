<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Membuat tabel guest_documents: foto KTP dan selfie yang diunggah tamu lewat
     * "Lengkapi profil" (TAM-18, revisi HRD #4). Satu baris per unggahan; foto yang
     * diganti tetap tersimpan untuk dokumentasi, foto terbaru per email dan jenis yang dipakai.
     */
    public function up(): void
    {
        Schema::create('guest_documents', function (Blueprint $table) {
            $table->id();
            $table->string('email', 254);
            $table->enum('type', ['ktp', 'selfie']);
            $table->binary('image');
            $table->string('mime_type', 20)->default('image/jpeg');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->foreignId('qr_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at');

            $table->index(['email', 'type', 'id']);
        });

        // Blueprint::binary() menghasilkan BLOB (maks. 64 KB); foto butuh MEDIUMBLOB.
        DB::statement('ALTER TABLE guest_documents MODIFY image MEDIUMBLOB NOT NULL');
    }

    /**
     * Menghapus tabel guest_documents.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_documents');
    }
};
