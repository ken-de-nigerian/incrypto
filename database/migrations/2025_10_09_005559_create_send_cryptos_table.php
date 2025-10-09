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
        Schema::create('send_cryptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token_symbol');
            $table->string('recipient_address');
            $table->decimal('amount', 20, 8);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('transaction_hash')->nullable();
            $table->decimal('fee', 20, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_cryptos');
    }
};
