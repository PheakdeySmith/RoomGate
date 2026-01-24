<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('occupant_user_id');
            $table->unsignedBigInteger('room_id');

            $table->date('start_date');
            $table->date('end_date');

            $table->unsignedBigInteger('monthly_rent_cents')->default(0);
            $table->unsignedBigInteger('deposit_cents')->default(0);
            $table->char('currency_code', 3)->default('USD');

            $table->string('billing_cycle', 20)->default('monthly'); // monthly|weekly|daily|custom
            $table->unsignedSmallInteger('payment_due_day')->default(1);

            $table->date('next_invoice_date')->nullable();
            $table->date('last_invoiced_through')->nullable();

            $table->string('contract_image_path', 255)->nullable();
            $table->string('status', 20)->default('active'); // active|pending|terminated|expired|cancelled

            $table->text('notes')->nullable();
            $table->boolean('auto_renew')->default(false);

            $table->timestampTz('terminated_at', 3)->nullable();
            $table->text('termination_reason')->nullable();

            $table->unsignedBigInteger('previous_contract_id')->nullable();

            $table->boolean('auto_payment')->default(false);
            $table->unsignedBigInteger('payment_method_id')->nullable();

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->unique(['id', 'tenant_id'], 'uq_contracts_id_tenant');

            $table->index(['tenant_id', 'room_id', 'status', 'start_date', 'end_date'], 'idx_contracts_room_status_dates');
            $table->index(['tenant_id', 'status', 'end_date'], 'idx_contracts_scope_status');
            $table->index(['tenant_id', 'occupant_user_id', 'status'], 'idx_contracts_occupant');
            $table->index(['tenant_id', 'deleted_at'], 'idx_contracts_tenant_deleted');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('occupant_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->restrictOnDelete();
            $table->foreign('previous_contract_id')->references('id')->on('contracts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
