<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('channel', 20)->default('email');
            $table->string('template_key', 120)->nullable();
            $table->string('to_address', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestampTz('scheduled_at', 3)->nullable();
            $table->timestampTz('sent_at', 3)->nullable();
            $table->string('dedupe_key', 160)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz(3);

            $table->unique('dedupe_key', 'uq_outbound_messages_dedupe');
            $table->index(['tenant_id', 'status'], 'idx_outbound_messages_tenant_status');
            $table->index(['scheduled_at', 'status'], 'idx_outbound_messages_schedule_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_messages');
    }
};
