<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 50)->default('info');
            $table->string('title', 160);
            $table->string('body', 255)->nullable();
            $table->string('icon', 80)->nullable();
            $table->string('link_url', 255)->nullable();
            $table->timestampTz('read_at', 3)->nullable();
            $table->timestampsTz(3);

            $table->index(['user_id', 'read_at'], 'idx_in_app_notifications_user_read');
            $table->index(['tenant_id', 'created_at'], 'idx_in_app_notifications_tenant_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
    }
};
