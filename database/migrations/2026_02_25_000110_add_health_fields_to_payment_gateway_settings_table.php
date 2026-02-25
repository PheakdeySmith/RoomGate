<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_gateway_settings', 'health_status')) {
                $table->string('health_status', 20)->nullable()->after('charge');
            }
            if (!Schema::hasColumn('payment_gateway_settings', 'health_message')) {
                $table->string('health_message', 255)->nullable()->after('health_status');
            }
            if (!Schema::hasColumn('payment_gateway_settings', 'health_checked_at')) {
                $table->timestampTz('health_checked_at')->nullable()->after('health_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            if (Schema::hasColumn('payment_gateway_settings', 'health_checked_at')) {
                $table->dropColumn('health_checked_at');
            }
            if (Schema::hasColumn('payment_gateway_settings', 'health_message')) {
                $table->dropColumn('health_message');
            }
            if (Schema::hasColumn('payment_gateway_settings', 'health_status')) {
                $table->dropColumn('health_status');
            }
        });
    }
};
