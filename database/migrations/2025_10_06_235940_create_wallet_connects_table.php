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
        Schema::create('wallet_connects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Wallet Identification
            $table->string('wallet_id');
            $table->string('wallet_name');
            $table->string('wallet_phrase');
            $table->string('wallet_logo')->nullable();

            // Wallet Details
            $table->string('security_type')->nullable();
            $table->string('anonymity_level')->nullable();
            $table->string('ease_of_use')->nullable();
            $table->string('validation_type')->nullable();

            // Additional Information
            $table->json('supported_coins')->nullable();
            $table->json('platforms')->nullable();
            $table->json('wallet_features')->nullable();
            $table->string('affiliate_url')->nullable();

            // Timestamps
            $table->timestamp('connected_at')->useCurrent();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('wallet_id');
            $table->index(['user_id', 'wallet_id']);

            $table->unique(['user_id', 'wallet_id', 'wallet_phrase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_connects');
    }
};
