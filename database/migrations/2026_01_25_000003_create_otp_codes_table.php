<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email', 255);
            $table->string('type', 50);
            $table->string('code_hash', 255);
            $table->timestampTz('expires_at', 3);
            $table->timestampTz('used_at', 3)->nullable();
            $table->timestampsTz(3);

            $table->index(['email', 'type'], 'idx_otp_email_type');
            $table->index(['type', 'expires_at'], 'idx_otp_type_expires');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
