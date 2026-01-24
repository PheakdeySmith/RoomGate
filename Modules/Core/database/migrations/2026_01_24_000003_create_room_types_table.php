<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();

            $table->string('status', 20)->default('active'); // active|inactive

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->unique(['tenant_id', 'name'], 'uq_room_types_tenant_name');
            $table->unique(['id', 'tenant_id'], 'uq_room_types_id_tenant');

            $table->index(['tenant_id', 'status'], 'idx_room_types_tenant_status');
            $table->index(['tenant_id', 'deleted_at'], 'idx_room_types_tenant_deleted');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
