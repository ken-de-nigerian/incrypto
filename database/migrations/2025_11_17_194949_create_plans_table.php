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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_time_settings_id')->constrained('plan_time_settings')->onDelete('cascade');
            $table->string('name');
            $table->decimal('minimum', 15)->default(0);
            $table->decimal('maximum', 15)->default(0);
            $table->integer('interest')->default(0);
            $table->integer('period')->default('1');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('capital_back_status', ['yes', 'no'])->default('yes');
            $table->integer('repeat_time')->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
