<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            // Kita butuh team_id agar fitur Multi-Tenancy aman
            if (!Schema::hasColumn('cash_flows', 'team_id')) {
                $table->foreignId('team_id')
                    ->after('id')
                    ->nullable() // Nullable dulu biar data lama aman
                    ->constrained()
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
