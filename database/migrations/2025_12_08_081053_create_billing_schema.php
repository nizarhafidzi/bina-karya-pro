<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Paket Harga
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Basic, Pro, Enterprise
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 10, 2);
            $table->integer('max_projects')->default(1); // Limitasi fitur
            $table->integer('max_users')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Langganan Per Team
        Schema::create('team_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            
            // Status: 'active', 'expired', 'canceled', 'trial'
            $table->string('status')->default('active'); 
            
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable(); // Jika NULL = Lifetime
            $table->timestamp('trial_ends_at')->nullable();
            
            $table->timestamps();
        });

        // 3. Tabel Riwayat Transaksi (Audit Log Pembayaran)
        Schema::create('saas_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->string('reference_no')->unique(); // Order ID dari Tripay
            $table->decimal('amount', 12, 2);
            $table->string('status'); // UNPAID, PAID, FAILED
            $table->string('payment_method')->nullable(); // QRIS, VA, dll
            $table->json('payment_gateway_response')->nullable(); // Simpan raw data response
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_transactions');
        Schema::dropIfExists('team_subscriptions');
        Schema::dropIfExists('plans');
    }
};