<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('flag_key', 120)->unique();
            $table->string('name', 191);
            $table->string('description', 500)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('owner', 191)->nullable();
            $table->date('sunset_date')->nullable();
            $table->timestampsTz(3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
