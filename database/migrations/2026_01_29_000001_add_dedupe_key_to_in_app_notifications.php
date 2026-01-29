<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_app_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('in_app_notifications', 'dedupe_key')) {
                $table->string('dedupe_key', 160)->nullable()->after('link_url');
            }
        });

        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->unique(['tenant_id', 'user_id', 'dedupe_key'], 'uq_in_app_notifications_tenant_user_dedupe');
        });
    }

    public function down(): void
    {
        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->dropUnique('uq_in_app_notifications_tenant_user_dedupe');
            if (Schema::hasColumn('in_app_notifications', 'dedupe_key')) {
                $table->dropColumn('dedupe_key');
            }
        });
    }
};
