<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_type_id')->nullable();

            $table->string('room_number', 255);
            $table->text('description')->nullable();
            $table->text('features')->nullable();
            $table->json('extra_metadata')->nullable();

            $table->string('size', 255)->nullable();
            $table->integer('floor')->nullable();

            $table->unsignedInteger('max_occupants')->default(1);

            $table->unsignedBigInteger('monthly_rent_cents')->default(0);
            $table->char('currency_code', 3)->default('USD');

            $table->string('status', 20)->default('available'); // available|occupied|maintenance|inactive

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->unique(['property_id', 'room_number'], 'uq_rooms_property_number');
            $table->unique(['id', 'tenant_id'], 'uq_rooms_id_tenant');

            $table->index(['tenant_id', 'status'], 'idx_rooms_tenant_status');
            $table->index(['tenant_id', 'deleted_at'], 'idx_rooms_tenant_deleted');
            $table->index(['property_id', 'status'], 'idx_rooms_property_status');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->restrictOnDelete();
            $table->foreign('room_type_id')->references('id')->on('room_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
