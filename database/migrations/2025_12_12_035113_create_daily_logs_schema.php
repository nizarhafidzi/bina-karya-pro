<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Log Harian (Jurnal Lapangan)
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_manager_id')->constrained('users'); // Siapa yang lapor
            
            $table->date('date');
            
            // Kondisi Alam & Tenaga
            $table->string('weather_am')->nullable(); // Cerah, Hujan, Mendung
            $table->string('weather_pm')->nullable();
            $table->integer('manpower_total')->default(0); // Jumlah tukang hadir
            
            // Catatan Kualitatif
            $table->text('material_note')->nullable(); // Material datang hari ini
            $table->text('problem_note')->nullable(); // Kendala lapangan
            $table->text('work_note')->nullable(); // Pekerjaan yang dilakukan
            
            $table->timestamps();
        });

        // Tabel Galeri Foto Proyek
        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_log_id')->constrained('daily_logs')->cascadeOnDelete();
            
            $table->string('path'); // Path file di storage
            $table->string('category')->default('progress'); // progress, issue, material
            $table->string('caption')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_images');
        Schema::dropIfExists('daily_logs');
    }
};