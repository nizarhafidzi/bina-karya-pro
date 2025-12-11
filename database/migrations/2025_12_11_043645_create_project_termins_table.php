<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_termins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            
            $table->string('name'); // e.g. "DP", "Termin 1 (20%)", "Retensi"
            
            // Syarat Progress Fisik untuk menagih termin ini
            $table->decimal('target_progress', 5, 2); // e.g. 20.00 (%)
            
            // Nilai Tagihan
            $table->decimal('percentage_value', 5, 2); // e.g. 15.00 (%)
            $table->decimal('nominal_value', 15, 2); // Rp 150.000.000
            
            // Workflow Status
            $table->string('status')->default('planned'); 
            // planned   : Belum saatnya
            // ready     : Progress fisik tercapai, siap ditagih (System Generated)
            // submitted : Invoice sudah dikirim ke Owner
            // paid      : Uang sudah diterima (Done)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_termins');
    }
};