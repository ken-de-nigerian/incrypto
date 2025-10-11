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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('live_trading_balance')->default(0);
            $table->decimal('demo_trading_balance')->default(10000);
            $table->string('profile_photo_path')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->enum('trading_status', ['live', 'demo'])->default('demo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
