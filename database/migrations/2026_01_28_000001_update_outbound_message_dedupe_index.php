<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropUnique('uq_outbound_messages_dedupe');
            $table->unique(['tenant_id', 'dedupe_key'], 'uq_outbound_messages_tenant_dedupe');
        });
    }

    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropUnique('uq_outbound_messages_tenant_dedupe');
            $table->unique('dedupe_key', 'uq_outbound_messages_dedupe');
        });
    }
};
