<?php

namespace Modules\Core\App\Services;

use App\Models\Contract;
use App\Models\Room;
use App\Models\UtilityMeter;
use App\Models\UtilityMeterReading;
use App\Models\UtilityRate;
use App\Models\UtilityType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UtilityInvoiceService
{
    public function resolvePeriodStart(Contract $contract, ?Carbon $periodStart = null): Carbon
    {
        if ($periodStart) {
            return $periodStart->copy()->startOfDay();
        }

        if ($contract->last_invoiced_through) {
            return Carbon::parse($contract->last_invoiced_through)->addDay()->startOfDay();
        }

        return Carbon::parse($contract->start_date)->startOfDay();
    }

    public function calculateRentCents(
        Contract $contract,
        Carbon $periodEnd,
        ?Carbon $periodStart = null,
        ?float $overrideAmount = null,
        bool $prorate = false
    ): int {
        if ($overrideAmount !== null) {
            return (int) round($overrideAmount * 100);
        }

        $rentCents = (int) ($contract->monthly_rent_cents ?? 0);
        if ($rentCents <= 0) {
            $room = $contract->room;
            if (! $room) {
                $room = Room::query()
                    ->where('tenant_id', $contract->tenant_id)
                    ->find($contract->room_id);
            }
            $rentCents = (int) ($room->monthly_rent_cents ?? 0);
        }
        if (! $prorate || $rentCents <= 0) {
            return $rentCents;
        }

        $start = $this->resolvePeriodStart($contract, $periodStart);
        $end = $periodEnd->copy()->startOfDay();
        if ($start->gt($end)) {
            return 0;
        }

        $daysInPeriod = $start->diffInDays($end) + 1;
        $daysInMonth = max(1, $end->daysInMonth);

        return (int) round($rentCents * ($daysInPeriod / $daysInMonth));
    }

    public function buildUtilityItems(Contract $contract, Carbon $periodEnd, ?Carbon $periodStart = null): array
    {
        $room = $contract->room;
        if (! $room) {
            $room = Room::query()
                ->where('tenant_id', $contract->tenant_id)
                ->find($contract->room_id);
        }
        if (! $room) {
            return [];
        }

        $start = $this->resolvePeriodStart($contract, $periodStart);
        $end = $periodEnd->copy()->startOfDay();
        if ($start->gt($end)) {
            return [];
        }

        $meters = UtilityMeter::query()
            ->where('tenant_id', $contract->tenant_id)
            ->where('room_id', $room->id)
            ->get(['id', 'utility_type_id', 'unit_of_measure']);

        if ($meters->isEmpty()) {
            return [];
        }

        $meterIds = $meters->pluck('id')->all();
        $usageRows = UtilityMeterReading::query()
            ->where('tenant_id', $contract->tenant_id)
            ->whereIn('meter_id', $meterIds)
            ->whereNotNull('usage_value')
            ->whereDate('reading_at', '>=', $start->toDateString())
            ->whereDate('reading_at', '<=', $end->toDateString())
            ->select('meter_id', DB::raw('SUM(usage_value) as total_usage'))
            ->groupBy('meter_id')
            ->get();

        if ($usageRows->isEmpty()) {
            return [];
        }

        $meterMap = $meters->keyBy('id');
        $usageByType = [];
        foreach ($usageRows as $row) {
            $meter = $meterMap->get($row->meter_id);
            if (! $meter) {
                continue;
            }
            $typeId = $meter->utility_type_id;
            $usageByType[$typeId] = ($usageByType[$typeId] ?? 0) + (float) $row->total_usage;
        }

        if (empty($usageByType)) {
            return [];
        }

        $utilityTypes = UtilityType::query()
            ->whereIn('id', array_keys($usageByType))
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($usageByType as $typeId => $usage) {
            if ($usage <= 0) {
                continue;
            }

            $rate = UtilityRate::query()
                ->where('tenant_id', $contract->tenant_id)
                ->where('utility_type_id', $typeId)
                ->where(function ($query) use ($room) {
                    $query->whereNull('property_id')
                        ->orWhere('property_id', $room->property_id);
                })
                ->where('effective_from', '<=', $end->toDateString())
                ->where(function ($query) use ($end) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $end->toDateString());
                })
                ->orderByRaw('CASE WHEN property_id IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('effective_from')
                ->first();

            if (! $rate) {
                continue;
            }

            $amountCents = (int) round($usage * (int) $rate->rate_cents);
            if ($amountCents <= 0) {
                continue;
            }

            $typeLabel = $utilityTypes[$typeId]->name ?? 'Utility';
            $description = sprintf(
                '%s usage (%s to %s)',
                $typeLabel,
                $start->toDateString(),
                $end->toDateString()
            );

            $items[] = [
                'description' => $description,
                'amount_cents' => $amountCents,
                'item_type' => 'utility',
                'ref_table' => 'utility_types',
                'ref_id' => $typeId,
                'usage_value' => $usage,
                'rate_cents' => (int) $rate->rate_cents,
                'unit' => $utilityTypes[$typeId]->unit_of_measure ?? null,
            ];
        }

        return $items;
    }
}
