<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('outbound_messages', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('sent_at');
            }
            if (!Schema::hasColumn('outbound_messages', 'bounced_at')) {
                $table->timestamp('bounced_at')->nullable()->after('failed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            if (Schema::hasColumn('outbound_messages', 'bounced_at')) {
                $table->dropColumn('bounced_at');
            }
            if (Schema::hasColumn('outbound_messages', 'failed_at')) {
                $table->dropColumn('failed_at');
            }
        });
    }
};
