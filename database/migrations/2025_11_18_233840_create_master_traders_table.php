<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_traders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('expertise', ['Newcomer', 'Growing talent', 'High achiever', 'Expert', 'Legend'])->default('Newcomer');
            $table->integer('risk_score')->default(1);
            $table->decimal('gain_percentage', 10)->default(0.00);
            $table->integer('copiers_count')->default(0);
            $table->decimal('commission_rate', 15)->nullable();
            $table->decimal('total_profit', 15)->default(0.00);
            $table->decimal('total_loss', 15)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->text('bio')->nullable();
            $table->integer('total_trades')->default(0);
            $table->decimal('win_rate', 5)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'expertise']);
            $table->index('gain_percentage');
            $table->index('copiers_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_traders');
    }
};
