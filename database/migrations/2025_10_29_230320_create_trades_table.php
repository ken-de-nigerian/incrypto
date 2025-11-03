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
            $table->string('pair', 20);
            $table->string('pair_name', 100);
            $table->enum('type', ['Up', 'Down']);
            $table->decimal('amount', 15);
            $table->unsignedSmallInteger('leverage')->default(1);
            $table->string('duration', 10);
            $table->decimal('entry_price', 15, 8);
            $table->decimal('exit_price', 15, 8)->nullable();
            $table->enum('status', ['Open', 'Closed'])->default('Open');
            $table->decimal('pnl', 15)->default(0);
            $table->enum('trading_mode', ['live', 'demo'])->default('demo');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_auto_close')->default(false);
            $table->timestamp('expiry_time');
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('trading_mode');
            $table->index(['user_id', 'status']);
            $table->index('expiry_time');
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
