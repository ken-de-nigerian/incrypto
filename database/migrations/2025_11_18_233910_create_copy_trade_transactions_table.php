<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copy_trade_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('copy_trade_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['up', 'down']);
            $table->decimal('amount', 15);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['copy_trade_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copy_trade_transactions');
    }
};
