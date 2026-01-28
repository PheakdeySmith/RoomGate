<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('model_id');
                $table->index('tenant_id', 'idx_audit_tenant');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'tenant_id')) {
                $table->dropIndex('idx_audit_tenant');
                $table->dropColumn('tenant_id');
            }
        });
    }
};
