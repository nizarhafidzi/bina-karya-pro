<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- 1. REFERENCE & MASTER DATA ---

        // Regions: Untuk indeks harga berdasarkan wilayah (Optional untuk V1, tapi disiapkan)
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        // Resources: Bahan, Upah, Alat
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete(); // Hybrid
            $table->string('name');
            $table->string('unit'); // m3, kg, org/hari
            $table->string('category'); // material, labor, equipment
            $table->string('code')->nullable(); // Kode SNI
            $table->decimal('default_price', 15, 2)->default(0); // Harga dasar
            $table->timestamps();
        });

        // AHS Master: Header Analisa (Misal: 1 m3 Galian Tanah)
        Schema::create('ahs_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete(); // Hybrid
            $table->string('name');
            $table->string('code')->nullable(); // Kode AHS SNI
            $table->decimal('total_price', 15, 2)->default(0); // Cache total harga
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // AHS Coefficients: Resep (Misal: 0.5 OH Pekerja untuk 1 m3 Galian)
        Schema::create('ahs_coefficients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ahs_master_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained(); // Link ke Resource
            $table->decimal('coefficient', 10, 4); // Koefisien (0.0500)
            $table->timestamps();
        });

        // --- 2. PROJECT PLANNING (RAB) ---

        // Projects: Header Proyek
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete(); // Strict
            $table->string('name');
            $table->string('code')->unique(); // No Kontrak/SPK
            $table->text('location')->nullable();
            $table->foreignId('region_id')->nullable()->constrained(); // Link ke wilayah harga
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('contract_value', 15, 2)->default(0);
            
            $table->string('status')->default('draft'); // draft, ongoing, finished
            $table->timestamps();
        });

        // WBS (Work Breakdown Structure): Struktur Pekerjaan
        Schema::create('wbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('wbs')->cascadeOnDelete(); // Self-referencing
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // RAB Items: Biaya per item WBS
        Schema::create('rab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wbs_id')->constrained('wbs')->cascadeOnDelete();
            $table->foreignId('ahs_master_id')->nullable()->constrained(); // Asal copy resep (optional)
            $table->decimal('qty', 12, 3)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->timestamps();
        });

        // RAB Item Materials (SNAPSHOT): Komponen penyusun harga
        // PENTING: Ini memisahkan RAB proyek dari Master Data. Jika master berubah, ini TIDAK berubah.
        Schema::create('rab_item_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_item_id')->constrained()->cascadeOnDelete();
            $table->string('resource_name'); // Simpan nama saat itu
            $table->string('unit');
            $table->decimal('coefficient', 10, 4);
            $table->decimal('price', 15, 2); // Simpan harga saat itu
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // Project Schedule: Rencana Bobot per Minggu (S-Curve Plan)
        Schema::create('project_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('week_number');
            $table->decimal('planned_progress', 5, 2); // % Rencana minggu ini (kumulatif atau parsial)
            $table->timestamps();
        });

        // --- 3. EXECUTION & MONITORING ---

        // Daily Reports: Laporan Harian Lapangan
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('weather_am')->nullable(); // Cerah, Hujan
            $table->string('weather_pm')->nullable();
            $table->text('notes')->nullable(); // Kendala lapangan
            $table->foreignId('site_manager_id')->constrained('users'); // Siapa yg input
            $table->timestamps();
        });
        
        // Progress Opname (Realisasi Mingguan/Bulanan)
        Schema::create('weekly_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('week_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('realized_progress', 5, 2); // % Realisasi
            $table->string('status')->default('submitted'); // submitted, approved
            $table->timestamps();
        });

        // --- 4. FINANCE ---

        // Cash Flow
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // in (termin), out (expense)
            $table->string('category'); // material, labor, operasional
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('status')->default('planned'); // planned, paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop urutan terbalik
        Schema::dropIfExists('cash_flows');
        Schema::dropIfExists('weekly_realizations');
        Schema::dropIfExists('daily_reports');
        Schema::dropIfExists('project_schedules');
        Schema::dropIfExists('rab_item_materials');
        Schema::dropIfExists('rab_items');
        Schema::dropIfExists('wbs');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('ahs_coefficients');
        Schema::dropIfExists('ahs_masters');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('regions');
    }
};