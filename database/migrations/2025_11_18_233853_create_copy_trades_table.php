<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copy_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('master_trader_id')->constrained('master_traders')->onDelete('cascade');
            $table->decimal('current_profit', 15)->default(0.00);
            $table->decimal('current_loss', 15)->default(0.00);
            $table->integer('multiplier')->default(1);
            $table->decimal('total_commission_paid', 15)->default(0.00);
            $table->enum('status', ['active', 'paused', 'stopped'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['master_trader_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copy_trades');
    }
};
