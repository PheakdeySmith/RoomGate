<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('outbound_messages', 'provider_message_id')) {
                $table->string('provider_message_id', 191)->nullable()->after('dedupe_key');
                $table->index(['provider_message_id'], 'idx_outbound_messages_provider_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            if (Schema::hasColumn('outbound_messages', 'provider_message_id')) {
                $table->dropIndex('idx_outbound_messages_provider_message');
                $table->dropColumn('provider_message_id');
            }
        });
    }
};
