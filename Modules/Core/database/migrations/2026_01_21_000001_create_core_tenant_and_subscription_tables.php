<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->string('status', 20)->default('active'); // active|suspended|closed
            $table->char('default_currency', 3)->default('USD');
            $table->string('timezone', 64)->default('UTC');
            $table->timestampsTz(3);
            $table->timestampTz('deleted_at', 3)->nullable();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->unsignedInteger('price_cents')->default(0);
            $table->char('currency_code', 3)->default('USD');
            $table->string('interval', 20)->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz(3);
        });

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

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('status', 20)->default('active'); // active|trialing|past_due|cancelled|expired
            $table->boolean('auto_renew')->default(true);
            $table->dateTimeTz('current_period_start');
            $table->dateTimeTz('current_period_end');
            $table->dateTimeTz('trial_ends_at')->nullable();
            $table->dateTimeTz('cancelled_at')->nullable();
            $table->string('provider', 50)->default('manual'); // bakong|stripe|manual
            $table->string('provider_ref', 255)->nullable();
            $table->timestampsTz(3);
            $table->timestampTz('deleted_at', 3)->nullable();

            $table->unique(['tenant_id', 'plan_id', 'status'], 'uq_sub_tenant_plan_status');
            $table->index(['tenant_id', 'status'], 'idx_sub_tenant_status');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_id');
            $table->string('invoice_number', 50)->unique();
            $table->unsignedInteger('amount_cents');
            $table->char('currency_code', 3)->default('USD');
            $table->string('status', 20)->default('unpaid'); // unpaid|paid|void
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->date('due_date');
            $table->dateTimeTz('paid_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz(3);

            $table->index(['tenant_id', 'status'], 'idx_sub_invoice_tenant_status');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_invoice_id');
            $table->unsignedInteger('amount_cents');
            $table->char('currency_code', 3)->default('USD');
            $table->string('provider', 50)->default('manual');
            $table->string('provider_ref', 255)->nullable();
            $table->string('status', 20)->default('pending'); // pending|paid|failed|cancelled
            $table->dateTimeTz('paid_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz(3);

            $table->index(['tenant_id', 'status'], 'idx_sub_payment_tenant_status');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('subscription_invoice_id')->references('id')->on('subscription_invoices')->cascadeOnDelete();
        });

        Schema::create('plan_limits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('plan_id');
            $table->string('limit_key', 64);
            $table->string('limit_value', 64);
            $table->timestampsTz(3);

            $table->unique(['plan_id', 'limit_key'], 'uq_plan_limit_key');
            $table->index(['plan_id', 'limit_key'], 'idx_plan_limit_key');

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('tenants');
    }
};
