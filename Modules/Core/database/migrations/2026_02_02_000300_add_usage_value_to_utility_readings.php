<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_meter_readings', function (Blueprint $table) {
            $table->decimal('usage_value', 12, 3)->nullable()->after('reading_value');
        });

        $tenantIds = DB::table('utility_meter_readings')
            ->select('tenant_id')
            ->distinct()
            ->pluck('tenant_id');

        foreach ($tenantIds as $tenantId) {
            $meterIds = DB::table('utility_meter_readings')
                ->where('tenant_id', $tenantId)
                ->select('meter_id')
                ->distinct()
                ->pluck('meter_id');

            foreach ($meterIds as $meterId) {
                $rows = DB::table('utility_meter_readings')
                    ->where('tenant_id', $tenantId)
                    ->where('meter_id', $meterId)
                    ->orderBy('reading_at')
                    ->orderBy('id')
                    ->get(['id', 'reading_value']);

                $previousValue = null;
                foreach ($rows as $row) {
                    $usageValue = $previousValue !== null ? (float) $row->reading_value - (float) $previousValue : null;
                    DB::table('utility_meter_readings')
                        ->where('id', $row->id)
                        ->update(['usage_value' => $usageValue]);
                    $previousValue = $row->reading_value;
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('utility_meter_readings', function (Blueprint $table) {
            $table->dropColumn('usage_value');
        });
    }
};
