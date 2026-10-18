<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Membuat tabel visit_signatures.
     */
    public function up(): void
    {
        Schema::create('visit_signatures', function (Blueprint $table) {
            $table->foreignId('visit_id')->primary()->constrained('guest_visits')->restrictOnDelete();
            $table->binary('image');
            $table->string('mime_type', 20)->default('image/png');
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->dateTime('created_at');
        });

        // Blueprint::binary() menghasilkan BLOB (maks. 64 KB); tanda tangan butuh MEDIUMBLOB.
        DB::statement('ALTER TABLE visit_signatures MODIFY image MEDIUMBLOB NOT NULL');
    }

    /**
     * Menghapus tabel visit_signatures.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_signatures');
    }
};
