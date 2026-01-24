<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id')->nullable(); // NULL = global catalog

            $table->string('name', 255);
            $table->string('status', 20)->default('active'); // active|inactive

            $table->timestampsTz(3);

            $table->unique(['tenant_id', 'name'], 'uq_property_types_scope_name');
            $table->index(['tenant_id', 'status'], 'idx_property_types_scope_status');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'property_type_id')) {
                $table->unsignedBigInteger('property_type_id')->nullable()->after('tenant_id');
                $table->index(['tenant_id', 'property_type_id'], 'idx_properties_tenant_type');
            }

            if (Schema::hasColumn('properties', 'property_type')) {
                $table->dropColumn('property_type');
            }

            $table->foreign('property_type_id')->references('id')->on('property_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'property_type_id')) {
                $table->dropForeign(['property_type_id']);
                $table->dropIndex('idx_properties_tenant_type');
                $table->dropColumn('property_type_id');
            }
            if (!Schema::hasColumn('properties', 'property_type')) {
                $table->string('property_type', 255)->nullable();
            }
        });

        Schema::dropIfExists('property_types');
    }
};
