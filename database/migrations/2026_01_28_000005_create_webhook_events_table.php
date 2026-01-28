<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('provider', 50);
            $table->string('event_type', 100)->nullable();
            $table->string('idempotency_key', 191)->nullable();
            $table->json('payload');
            $table->string('status', 20)->default('received');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['provider', 'event_type'], 'idx_webhook_provider_event');
            $table->unique(['provider', 'idempotency_key'], 'uq_webhook_idempotency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
