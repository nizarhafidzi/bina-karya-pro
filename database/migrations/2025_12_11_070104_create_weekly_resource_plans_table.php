<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_resource_plans', function (Blueprint $table) {
            $table->id();
            
            // Konteks Proyek & Tim
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            
            // Dimensi Waktu
            $table->integer('week');
            
            // Dimensi Sumber Daya (Apa yang dibutuhkan?)
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            
            // Kuantitas
            // system_qty: Hasil hitungan otomatis komputer (Schedule % * RAB Qty)
            $table->decimal('system_qty', 15, 4)->default(0);
            
            // adjusted_qty: Override manual oleh Site Manager (jika perlu stok lebih)
            $table->decimal('adjusted_qty', 15, 4)->nullable();
            
            // Satuan (Snapshot dari Resource master agar tidak berubah historynya)
            $table->string('unit');

            $table->timestamps();

            // Indexing agar query dashboard cepat
            // Kita sering query: "Bahan apa saja yang butuh di Project X Minggu Y?"
            $table->unique(['project_id', 'week', 'resource_id'], 'unique_weekly_resource');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_resource_plans');
    }
};