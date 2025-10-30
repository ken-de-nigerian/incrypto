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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('pair');
            $table->string('pair_name');
            $table->enum('type', ['Buy', 'Sell']);
            $table->enum('status', ['Open', 'Closed', 'Pending'])->default('Pending');
            $table->decimal('entry_price', 10, 4);
            $table->decimal('amount', 12);
            $table->integer('leverage')->default(1);
            $table->decimal('stop_loss', 10, 4)->nullable();
            $table->decimal('take_profit', 10, 4)->nullable();
            $table->decimal('pnl', 12)->default(0);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
