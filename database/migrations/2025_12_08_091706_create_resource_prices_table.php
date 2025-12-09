<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            // team_id: Menandakan siapa yang menetapkan harga ini (Tenant atau Global)
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete(); 
            $table->year('year');
            $table->decimal('price', 15, 2);
            $table->timestamps();
            
            // Mencegah duplikasi harga untuk resource yg sama di region & tahun yg sama oleh team yg sama
            $table->unique(['resource_id', 'region_id', 'team_id', 'year'], 'price_unique_constraint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_prices');
    }
};