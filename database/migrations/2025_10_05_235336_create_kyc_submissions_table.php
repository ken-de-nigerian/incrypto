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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->after('status');
            $table->string('last_name')->after('first_name');
            $table->string('phone_number')->after('last_name');
            $table->date('date_of_birth')->after('phone_number');
            $table->string('country')->after('date_of_birth');
            $table->string('state')->after('country');
            $table->string('city')->after('state');
            $table->string('address')->after('city');
            $table->enum('status', ['pending', 'verified', 'rejected', 'unverified'])->default('pending');
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
