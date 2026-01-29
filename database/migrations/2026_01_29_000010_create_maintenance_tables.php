<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('category', 50)->default('other');
            $table->string('status', 20)->default('open'); // open|in_progress|resolved|closed|cancelled
            $table->string('priority', 20)->default('medium'); // low|medium|high|urgent
            $table->timestampTz('requested_at', 3)->useCurrent();
            $table->timestampTz('first_response_at', 3)->nullable();
            $table->timestampTz('resolved_at', 3)->nullable();
            $table->timestampTz('closed_at', 3)->nullable();
            $table->unsignedSmallInteger('satisfaction_rating')->nullable();
            $table->text('satisfaction_comment')->nullable();
            $table->json('extra_metadata')->nullable();
            $table->timestampsTz(3);
            $table->timestampTz('deleted_at', 3)->nullable();

            $table->index(['tenant_id', 'status', 'priority', 'requested_at'], 'idx_mr_scope_status');
            $table->index(['tenant_id', 'property_id', 'status'], 'idx_mr_property');
            $table->index(['tenant_id', 'room_id', 'status'], 'idx_mr_room');
            $table->index(['tenant_id', 'assigned_to_user_id', 'status'], 'idx_mr_assigned');
            $table->index(['tenant_id', 'deleted_at'], 'idx_mr_tenant_deleted');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
        });

        Schema::create('maintenance_status_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('maintenance_request_id');
            $table->unsignedBigInteger('changed_by_user_id');
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('note', 255)->nullable();
            $table->timestampTz('created_at', 3)->useCurrent();

            $table->index(['tenant_id', 'maintenance_request_id', 'created_at'], 'idx_mse_request_time');
            $table->index(['tenant_id', 'to_status', 'created_at'], 'idx_mse_to_status_time');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('maintenance_request_id')->references('id')->on('maintenance_requests')->cascadeOnDelete();
            $table->foreign('changed_by_user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('maintenance_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('maintenance_request_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestampTz('created_at', 3)->useCurrent();

            $table->index(['tenant_id', 'maintenance_request_id', 'created_at'], 'idx_mc_request_time');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('maintenance_request_id')->references('id')->on('maintenance_requests')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('maintenance_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('maintenance_request_id');
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->unsignedBigInteger('uploaded_by_user_id');
            $table->string('file_path', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->timestampTz('created_at', 3)->useCurrent();

            $table->index(['tenant_id', 'maintenance_request_id', 'created_at'], 'idx_ma_request_time');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('maintenance_request_id')->references('id')->on('maintenance_requests')->cascadeOnDelete();
            $table->foreign('comment_id')->references('id')->on('maintenance_comments')->nullOnDelete();
            $table->foreign('uploaded_by_user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('maintenance_request_id');
            $table->string('vendor_name', 255)->nullable();
            $table->timestampTz('scheduled_for', 3)->nullable();
            $table->timestampTz('completed_at', 3)->nullable();
            $table->unsignedBigInteger('cost_cents')->nullable();
            $table->char('currency_code', 3)->default('USD');
            $table->string('status', 20)->default('created'); // created|scheduled|in_progress|completed|cancelled
            $table->text('notes')->nullable();
            $table->timestampsTz(3);

            $table->index(['tenant_id', 'maintenance_request_id'], 'idx_wo_request');
            $table->index(['tenant_id', 'status', 'scheduled_for'], 'idx_wo_status');

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('maintenance_request_id')->references('id')->on('maintenance_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('maintenance_attachments');
        Schema::dropIfExists('maintenance_comments');
        Schema::dropIfExists('maintenance_status_events');
        Schema::dropIfExists('maintenance_requests');
    }
};
