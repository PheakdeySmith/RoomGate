<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 255);
            $table->string('property_type', 255)->nullable();
            $table->text('description')->nullable();

            $table->text('address_line_1')->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('state_province', 255)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('country', 64)->nullable();

            $table->integer('year_built')->nullable();
            $table->string('cover_image_path', 255)->nullable();
            $table->json('extra_metadata')->nullable();

            $table->string('status', 20)->default('active'); // active|inactive|archived

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->unique(['id', 'tenant_id'], 'uq_properties_id_tenant');
            $table->index(['tenant_id', 'status'], 'idx_properties_tenant_status');
            $table->index(['tenant_id', 'deleted_at'], 'idx_properties_tenant_deleted');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
