<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->text('value')->change();
            $table->string('key', 500)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->string('value', 191)->change();
        });
    }
};
