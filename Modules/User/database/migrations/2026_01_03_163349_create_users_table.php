<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->timestampTz('email_verified_at', 3)->nullable();

            $table->string('password', 255);
            $table->string('phone', 32)->nullable();
            $table->string('avatar_path', 255)->nullable();

            $table->string('status', 20)->default('active');
            $table->rememberToken();

            $table->string('platform_role', 30)->default('none');

            $table->timestampsTz(3);

            // ✅ This creates deleted_at
            $table->softDeletesTz('deleted_at', 3);

            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
