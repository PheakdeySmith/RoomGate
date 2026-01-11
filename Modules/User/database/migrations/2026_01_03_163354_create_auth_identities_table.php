<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth_identities', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');

            $table->string('provider', 50);          // google, telegram, apple, microsoft...
            $table->string('provider_user_id', 191); // provider unique id
            $table->string('email', 255)->nullable();

            // Store encrypted in app if you keep them
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestampTz('expires_at', 3)->nullable();
            $table->text('scopes')->nullable();

            $table->json('meta_json')->nullable();
            $table->json('raw_profile_json')->nullable();

            $table->timestampsTz(3);

            $table->unique(['provider', 'provider_user_id'], 'uq_provider_user');
            $table->index('user_id', 'idx_ai_user');
            $table->index(['provider', 'email'], 'idx_ai_provider_email');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_identities');
    }
};
