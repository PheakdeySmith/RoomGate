<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('utility_meters') && Schema::hasColumn('utility_meters', 'provider_id')) {
            Schema::table('utility_meters', function (Blueprint $table) {
                $table->dropForeign(['provider_id']);
                $table->dropColumn('provider_id');
            });
        }

        if (Schema::hasTable('utility_bills') && Schema::hasColumn('utility_bills', 'provider_id')) {
            Schema::table('utility_bills', function (Blueprint $table) {
                $table->dropForeign(['provider_id']);
                $table->dropColumn('provider_id');
            });
        }

        Schema::dropIfExists('utility_providers');
    }

    public function down(): void
    {
        if (!Schema::hasTable('utility_providers')) {
            Schema::create('utility_providers', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('utility_type_id')->nullable();

                $table->string('name', 255);
                $table->string('account_number', 100)->nullable();
                $table->string('contact_name', 255)->nullable();
                $table->string('contact_phone', 50)->nullable();
                $table->string('contact_email', 255)->nullable();
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();

                $table->timestampsTz(3);
                $table->softDeletesTz('deleted_at', 3);

                $table->unique(['tenant_id', 'name'], 'uq_utility_providers_name');
                $table->index(['tenant_id', 'utility_type_id'], 'idx_utility_providers_type');
                $table->index(['tenant_id', 'status'], 'idx_utility_providers_status');

                $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
                $table->foreign('utility_type_id')->references('id')->on('utility_types')->nullOnDelete();
            });
        }

        if (Schema::hasTable('utility_meters') && !Schema::hasColumn('utility_meters', 'provider_id')) {
            Schema::table('utility_meters', function (Blueprint $table) {
                $table->unsignedBigInteger('provider_id')->nullable()->after('utility_type_id');
                $table->foreign('provider_id')->references('id')->on('utility_providers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('utility_bills') && !Schema::hasColumn('utility_bills', 'provider_id')) {
            Schema::table('utility_bills', function (Blueprint $table) {
                $table->unsignedBigInteger('provider_id')->nullable()->after('meter_id');
                $table->foreign('provider_id')->references('id')->on('utility_providers')->nullOnDelete();
            });
        }
    }
};
