<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_realizations', function (Blueprint $table) {
            // Tambahkan kolom realized_progress jika belum ada
            if (!Schema::hasColumn('weekly_realizations', 'realized_progress')) {
                // Simpan total realisasi mingguan (misal 2.63%)
                $table->decimal('realized_progress', 5, 2)->default(0)->after('end_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('weekly_realizations', function (Blueprint $table) {
            if (Schema::hasColumn('weekly_realizations', 'realized_progress')) {
                $table->dropColumn('realized_progress');
            }
        });
    }
};