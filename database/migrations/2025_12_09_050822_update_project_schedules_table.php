<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_schedules', function (Blueprint $table) {
            // 1. Tambah kolom project_id
            if (!Schema::hasColumn('project_schedules', 'project_id')) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->after('team_id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            // 2. Ubah rab_item_id jadi nullable (agar bisa simpan jadwal global tanpa item spesifik)
            // Note: Pastikan kamu sudah install 'doctrine/dbal' jika pakai ->change(), 
            // TAPI cara aman SQL raw ini lebih kompatibel tanpa install paket tambahan:
            $table->unsignedBigInteger('rab_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('project_schedules', function (Blueprint $table) {
            // Hapus project_id
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');

            // Kembalikan rab_item_id jadi wajib (Warning: Data global yg rab_item_id-nya null akan error)
            $table->unsignedBigInteger('rab_item_id')->nullable(false)->change();
        });
    }
};