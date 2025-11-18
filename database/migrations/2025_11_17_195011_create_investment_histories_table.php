<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('investment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->decimal('amount', 15);
            $table->decimal('interest', 15)->default(0);
            $table->integer('period')->default(0);
            $table->integer('repeat_time')->default(0);
            $table->integer('repeat_time_count')->default(0);
            $table->timestamp('next_time')->nullable();
            $table->timestamp('last_time')->nullable();
            $table->enum('status', ['running', 'completed', 'cancelled'])->default('running');
            $table->string('capital_back_status')->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_histories');
    }
};
