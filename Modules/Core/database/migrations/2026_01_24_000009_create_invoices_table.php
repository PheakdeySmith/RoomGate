<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('contract_id');

            $table->string('invoice_number', 64);

            $table->date('issue_date');
            $table->date('due_date');

            $table->char('currency_code', 3)->default('USD');

            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedBigInteger('paid_cents')->default(0);

            $table->string('status', 20)->default('draft'); // draft|sent|paid|partial|overdue|void
            $table->timestampTz('sent_at', 3)->nullable();

            $table->timestampTz('last_reminder_at', 3)->nullable();
            $table->timestampTz('next_reminder_at', 3)->nullable();
            $table->unsignedInteger('reminder_count')->default(0);

            $table->text('notes')->nullable();

            $table->timestampsTz(3);
            $table->softDeletesTz('deleted_at', 3);

            $table->unique(['tenant_id', 'invoice_number'], 'uq_inv_number');

            $table->index(['tenant_id', 'status', 'due_date'], 'idx_inv_due');
            $table->index(['tenant_id', 'contract_id', 'issue_date'], 'idx_inv_contract_issue');
            $table->index(['tenant_id', 'deleted_at'], 'idx_inv_tenant_deleted');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
