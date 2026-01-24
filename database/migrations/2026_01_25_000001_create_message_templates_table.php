<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('key', 120);
            $table->string('name', 120);
            $table->string('channel', 20)->default('email');
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz(3);

            $table->unique(['tenant_id', 'key'], 'uq_message_templates_tenant_key');
            $table->index(['key', 'channel'], 'idx_message_templates_key_channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
