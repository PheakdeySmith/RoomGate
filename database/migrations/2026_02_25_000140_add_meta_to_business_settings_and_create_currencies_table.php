<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('business_settings', 'meta')) {
                $table->json('meta')->nullable()->after('favicon_path');
            }
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('code', 3)->unique();
            $table->string('name', 80);
            $table->string('symbol', 10)->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->enum('symbol_position', ['prefix', 'suffix'])->default('prefix');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestampsTz(3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');

        Schema::table('business_settings', function (Blueprint $table) {
            if (Schema::hasColumn('business_settings', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};

