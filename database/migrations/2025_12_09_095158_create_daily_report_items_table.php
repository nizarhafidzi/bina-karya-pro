<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rab_item_id')->constrained('rab_items')->cascadeOnDelete();
            // Opsional: Bisa tambah kolom 'volume_daily' jika mau detail, tapi checklist saja sudah cukup
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_items');
    }
};