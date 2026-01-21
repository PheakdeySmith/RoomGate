<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');

            $table->string('role', 20)->default('tenant');   // owner|admin|staff|tenant
            $table->string('status', 20)->default('active'); // active|invited|disabled

            $table->timestampsTz(3);

            $table->primary(['tenant_id', 'user_id']);
            $table->index('user_id', 'idx_tu_user');
            $table->index(['tenant_id', 'role'], 'idx_tu_tenant_role');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_users');
    }
};
