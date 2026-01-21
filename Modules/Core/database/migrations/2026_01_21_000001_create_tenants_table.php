<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->string('status', 20)->default('active'); // active|suspended|closed
            $table->char('default_currency', 3)->default('USD');
            $table->string('timezone', 64)->default('UTC');
            $table->timestampsTz(3);
            $table->softDeletesTz(3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
