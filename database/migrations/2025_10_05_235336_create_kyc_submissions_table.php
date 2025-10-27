<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number');
            $table->date('date_of_birth');
            $table->string('country');
            $table->string('state');
            $table->string('city');
            $table->string('address');
            $table->string('id_proof_type');
            $table->string('id_front_proof_path');
            $table->string('id_back_proof_path')->nullable();
            $table->string('address_proof_type');
            $table->string('address_front_proof_path');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_submissions');
    }
};
