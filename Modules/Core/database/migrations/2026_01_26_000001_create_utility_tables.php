<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_types', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id')->nullable(); // null = global catalog
            $table->string('code', 50)->nullable();
            $table->string('name', 255);
            $table->string('unit_of_measure', 32);
            $table->string('billing_type', 20)->default('metered'); // metered|flat_rate
            $table->boolean('is_active')->default(true);

            $table->timestampsTz(3);

            $table->unique(['tenant_id', 'name'], 'uq_utility_types_scope_name');
            $table->index(['tenant_id', 'is_active'], 'idx_utility_types_active');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('utility_providers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('utility_type_id')->nullable();

            $table->string('name', 255);
            $table->string('account_number', 100)->nullable();
            $table->string('contact_name', 255)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('status', 20)->default('active'); // active|inactive
            $table->text('notes')->nullable();

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->unique(['tenant_id', 'name'], 'uq_utility_providers_name');
            $table->index(['tenant_id', 'utility_type_id'], 'idx_utility_providers_type');
            $table->index(['tenant_id', 'status'], 'idx_utility_providers_status');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('utility_type_id')->references('id')->on('utility_types')->nullOnDelete();
        });

        Schema::create('utility_meters', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('utility_type_id');
            $table->unsignedBigInteger('provider_id')->nullable();

            $table->string('meter_code', 64);
            $table->string('unit_of_measure', 32);
            $table->string('status', 20)->default('active'); // active|inactive
            $table->date('installed_at')->nullable();

            $table->decimal('last_reading_value', 12, 3)->nullable();
            $table->timestampTz('last_reading_at', 3)->nullable();
            $table->json('extra_metadata')->nullable();

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->unique(['tenant_id', 'meter_code'], 'uq_utility_meters_code');
            $table->index(['tenant_id', 'property_id'], 'idx_utility_meters_property');
            $table->index(['tenant_id', 'room_id'], 'idx_utility_meters_room');
            $table->index(['tenant_id', 'utility_type_id'], 'idx_utility_meters_type');
            $table->index(['tenant_id', 'deleted_at'], 'idx_utility_meters_deleted');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->restrictOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
            $table->foreign('utility_type_id')->references('id')->on('utility_types')->restrictOnDelete();
            $table->foreign('provider_id')->references('id')->on('utility_providers')->nullOnDelete();
        });

        Schema::create('utility_meter_readings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('meter_id');
            $table->decimal('reading_value', 12, 3);
            $table->timestampTz('reading_at', 3);
            $table->unsignedBigInteger('recorded_by_user_id')->nullable();
            $table->text('notes')->nullable();

            $table->timestampsTz(3);

            $table->index(['tenant_id', 'meter_id', 'reading_at'], 'idx_utility_readings_time');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('meter_id')->references('id')->on('utility_meters')->cascadeOnDelete();
            $table->foreign('recorded_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('utility_rates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('utility_type_id');

            $table->unsignedBigInteger('rate_cents');
            $table->char('currency_code', 3)->default('USD');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->timestampsTz(3);

            $table->index(['tenant_id', 'property_id', 'utility_type_id'], 'idx_utility_rates_scope');
            $table->index(['tenant_id', 'effective_from', 'effective_to'], 'idx_utility_rates_dates');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
            $table->foreign('utility_type_id')->references('id')->on('utility_types')->restrictOnDelete();
        });

        Schema::create('utility_bills', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('utility_type_id');
            $table->unsignedBigInteger('meter_id')->nullable();
            $table->unsignedBigInteger('provider_id')->nullable();

            $table->date('billing_period_start');
            $table->date('billing_period_end');

            $table->unsignedBigInteger('start_reading_id')->nullable();
            $table->unsignedBigInteger('end_reading_id')->nullable();
            $table->decimal('usage_amount', 12, 3)->nullable();
            $table->unsignedBigInteger('unit_cost_cents')->default(0);

            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->char('currency_code', 3)->default('USD');

            $table->string('status', 20)->default('draft'); // draft|sent|paid|overdue|void
            $table->date('issued_at')->nullable();
            $table->date('due_date')->nullable();
            $table->timestampTz('paid_at', 3)->nullable();
            $table->text('notes')->nullable();

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->unique(['tenant_id', 'contract_id', 'utility_type_id', 'billing_period_end'], 'uq_utility_bills_period');
            $table->index(['tenant_id', 'status'], 'idx_utility_bills_status');
            $table->index(['tenant_id', 'billing_period_end'], 'idx_utility_bills_period');
            $table->index(['tenant_id', 'contract_id'], 'idx_utility_bills_contract');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->restrictOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->restrictOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
            $table->foreign('utility_type_id')->references('id')->on('utility_types')->restrictOnDelete();
            $table->foreign('meter_id')->references('id')->on('utility_meters')->nullOnDelete();
            $table->foreign('provider_id')->references('id')->on('utility_providers')->nullOnDelete();
            $table->foreign('start_reading_id')->references('id')->on('utility_meter_readings')->nullOnDelete();
            $table->foreign('end_reading_id')->references('id')->on('utility_meter_readings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_bills');
        Schema::dropIfExists('utility_rates');
        Schema::dropIfExists('utility_meter_readings');
        Schema::dropIfExists('utility_meters');
        Schema::dropIfExists('utility_providers');
        Schema::dropIfExists('utility_types');
    }
};
