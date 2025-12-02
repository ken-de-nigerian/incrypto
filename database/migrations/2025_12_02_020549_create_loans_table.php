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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->decimal('loan_amount', 15);
            $table->decimal('interest_rate', 5);
            $table->integer('tenure_months');
            $table->decimal('monthly_emi', 15)->nullable();
            $table->decimal('total_interest', 15)->nullable();
            $table->decimal('total_payment', 15)->nullable();
            $table->longText('loan_reason')->nullable();
            $table->longText('loan_collateral')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('repayed_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
