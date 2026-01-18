<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('guard_name');
            $table->boolean('is_system')->default(false)->after('status');
            $table->index('status');
            $table->index('is_system');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('guard_name');
            $table->boolean('is_system')->default(false)->after('status');
            $table->index('status');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['is_system']);
            $table->dropColumn(['status', 'is_system']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['is_system']);
            $table->dropColumn(['status', 'is_system']);
        });
    }
};
