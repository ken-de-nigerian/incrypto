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
            $table->string('referral_code')->unique();
            $table->string('profile_photo_path')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->text('seed_phrase')->nullable();
            $table->enum('seed_phrase_status', ['generated', 'skipped'])->default('skipped');
            $table->timestamp('seed_phrase_skipped_at')->nullable();
            $table->timestamp('seed_phrase_expires_at')->nullable();
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
