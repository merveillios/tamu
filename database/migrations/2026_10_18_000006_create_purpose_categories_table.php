<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Membuat tabel purpose_categories.
     */
    public function up(): void
    {
        Schema::create('purpose_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->foreignId('suggested_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['location_id', 'name']);
        });
    }

    /**
     * Menghapus tabel purpose_categories.
     */
    public function down(): void
    {
        Schema::dropIfExists('purpose_categories');
    }
};
