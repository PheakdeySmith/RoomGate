<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Property;
use App\Models\UtilityBill;
use App\Models\UtilityMeter;
use App\Models\UtilityMeterReading;
use App\Models\UtilityRate;
use App\Models\UtilityType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;
use Carbon\Carbon;

class UtilityRateController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [UtilityRate::class, $tenant->id]);

        $propertyContext = $this->resolvePropertyContext($request, $tenant->id);

        $filterMonth = (int) $request->query('month', Carbon::now()->month);
        $filterYear = (int) $request->query('year', Carbon::now()->year);
        if ($filterMonth < 1 || $filterMonth > 12) {
            $filterMonth = Carbon::now()->month;
        }
        if ($filterYear < 2000 || $filterYear > (int) Carbon::now()->year + 5) {
            $filterYear = Carbon::now()->year;
        }

        $periodStart = Carbon::create($filterYear, $filterMonth, 1)->startOfMonth();
        $periodEnd = Carbon::create($filterYear, $filterMonth, 1)->endOfMonth();

        $ratesQuery = UtilityRate::query()
            ->with(['property', 'utilityType'])
            ->where('tenant_id', $tenant->id);

        if ($propertyContext) {
            $ratesQuery->where(function ($query) use ($propertyContext) {
                $query->whereNull('property_id')
                    ->orWhere('property_id', $propertyContext->id);
            });
        }

        $rates = $ratesQuery
            ->orderByDesc('effective_from')
            ->get();

        $properties = Property::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $utilityTypes = UtilityType::query()
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $appliedRates = collect();
        $totalRateCents = 0;
        $totalConsumption = 0.0;
        $totalBillableUsage = 0.0;
        $totalBillableCents = 0;
        $dailyUsageRateCents = [];
        $dailyConsumption = [];
        $dailyBillableUsage = [];
        $dailyBillableCents = [];
        $electricityTotals = ['consumption' => 0.0, 'bill_cents' => 0];
        $waterTotals = ['consumption' => 0.0, 'bill_cents' => 0];
        $dailyElectricUsage = [];
        $dailyWaterUsage = [];

        if ($propertyContext) {
            $today = Carbon::now()->toDateString();
            $applicableRates = UtilityRate::query()
                ->with('utilityType')
                ->where('tenant_id', $tenant->id)
                ->where(function ($query) use ($propertyContext) {
                    $query->whereNull('property_id')
                        ->orWhere('property_id', $propertyContext->id);
                })
                ->where('effective_from', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $today);
                })
                ->orderByRaw('CASE WHEN property_id IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('effective_from')
                ->get();

            $rateByType = [];
            foreach ($applicableRates as $rate) {
                $typeId = $rate->utility_type_id;
                if (!isset($rateByType[$typeId])) {
                    $rateByType[$typeId] = $rate;
                    $totalRateCents += (int) ($rate->rate_cents ?? 0);
                }
            }

            $appliedRates = collect($rateByType)->values();

            $meterIds = UtilityMeter::query()
                ->where('tenant_id', $tenant->id)
                ->where('property_id', $propertyContext->id)
                ->pluck('id');

            $daysInMonth = $periodStart->daysInMonth;
            $dateIndexMap = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::create($filterYear, $filterMonth, $day)->toDateString();
                $dateIndexMap[$date] = $day - 1;
                $dailyUsageRateCents[$day - 1] = 0;
                $dailyConsumption[$day - 1] = 0;
                $dailyBillableUsage[$day - 1] = 0;
                $dailyBillableCents[$day - 1] = 0;
                $dailyElectricUsage[$day - 1] = 0;
                $dailyWaterUsage[$day - 1] = 0;
            }

            $utilityTypeLookup = $utilityTypes->keyBy('id');
            $electricTypeId = optional($utilityTypes->first(function ($type) {
                return str_contains(strtolower($type->name ?? ''), 'electric');
            }))->id;
            $waterTypeId = optional($utilityTypes->first(function ($type) {
                return str_contains(strtolower($type->name ?? ''), 'water');
            }))->id;

            if ($meterIds->isNotEmpty()) {
                $meters = UtilityMeter::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('id', $meterIds)
                    ->get(['id', 'utility_type_id']);

                $meterTypeMap = $meters->pluck('utility_type_id', 'id')->all();
                $readings = UtilityMeterReading::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('meter_id', $meterIds)
                    ->whereBetween('reading_at', [$periodStart, $periodEnd])
                    ->orderBy('meter_id')
                    ->orderBy('reading_at')
                    ->get(['meter_id', 'reading_value', 'reading_at']);

                $meterFirstLast = [];
                foreach ($readings as $reading) {
                    $meterId = $reading->meter_id;
                    if (!isset($meterFirstLast[$meterId])) {
                        $meterFirstLast[$meterId] = [
                            'first' => $reading->reading_value,
                            'last' => $reading->reading_value,
                        ];
                    } else {
                        $meterFirstLast[$meterId]['last'] = $reading->reading_value;
                    }
                }

                foreach ($meterFirstLast as $meterId => $group) {
                    if ($group['last'] >= $group['first']) {
                        $diff = $group['last'] - $group['first'];
                        $totalConsumption += $diff;
                        $typeId = $meterTypeMap[$meterId] ?? null;
                        if ($typeId === $electricTypeId) {
                            $electricityTotals['consumption'] += $diff;
                        } elseif ($typeId === $waterTypeId) {
                            $waterTotals['consumption'] += $diff;
                        }
                    }
                }
            }

            $totalBillableUsage = (float) UtilityBill::query()
                ->where('tenant_id', $tenant->id)
                ->where('property_id', $propertyContext->id)
                ->whereBetween('billing_period_end', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->sum('usage_amount');

            $totalBillableCents = (int) UtilityBill::query()
                ->where('tenant_id', $tenant->id)
                ->where('property_id', $propertyContext->id)
                ->whereBetween('billing_period_end', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->sum('total_cents');

            $bills = UtilityBill::query()
                ->where('tenant_id', $tenant->id)
                ->where('property_id', $propertyContext->id)
                ->whereBetween('billing_period_end', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->get(['billing_period_end', 'usage_amount', 'total_cents', 'utility_type_id']);

            foreach ($bills as $bill) {
                $date = optional($bill->billing_period_end)->toDateString();
                if (!$date || !isset($dateIndexMap[$date])) {
                    continue;
                }
                $index = $dateIndexMap[$date];
                $dailyBillableUsage[$index] += (float) ($bill->usage_amount ?? 0);
                $dailyBillableCents[$index] += (int) ($bill->total_cents ?? 0);

                if ($bill->utility_type_id === $electricTypeId) {
                    $dailyElectricUsage[$index] += (float) ($bill->usage_amount ?? 0);
                } elseif ($bill->utility_type_id === $waterTypeId) {
                    $dailyWaterUsage[$index] += (float) ($bill->usage_amount ?? 0);
                }
            }

            if ($electricTypeId) {
                $electricityTotals['bill_cents'] = (int) UtilityBill::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('property_id', $propertyContext->id)
                    ->where('utility_type_id', $electricTypeId)
                    ->whereBetween('billing_period_end', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->sum('total_cents');
            }
            if ($waterTypeId) {
                $waterTotals['bill_cents'] = (int) UtilityBill::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('property_id', $propertyContext->id)
                    ->where('utility_type_id', $waterTypeId)
                    ->whereBetween('billing_period_end', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->sum('total_cents');
            }

            $ratesForChart = UtilityRate::query()
                ->where('tenant_id', $tenant->id)
                ->where(function ($query) use ($propertyContext) {
                    $query->whereNull('property_id')
                        ->orWhere('property_id', $propertyContext->id);
                })
                ->where('effective_from', '<=', $periodEnd->toDateString())
                ->where(function ($query) use ($periodStart) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $periodStart->toDateString());
                })
                ->orderByDesc('effective_from')
                ->get();

            $ratesByType = [];
            foreach ($ratesForChart as $rate) {
                $typeId = $rate->utility_type_id;
                if (!isset($ratesByType[$typeId])) {
                    $ratesByType[$typeId] = ['property' => [], 'global' => []];
                }
                $bucket = $rate->property_id ? 'property' : 'global';
                $ratesByType[$typeId][$bucket][] = $rate;
            }

            foreach ($ratesByType as $typeId => $groups) {
                foreach (['property', 'global'] as $groupKey) {
                    usort($groups[$groupKey], function ($a, $b) {
                        return $b->effective_from <=> $a->effective_from;
                    });
                }

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = Carbon::create($filterYear, $filterMonth, $day)->toDateString();
                    $rate = null;
                    foreach ($groups['property'] as $candidate) {
                        if ($candidate->effective_from <= $date &&
                            (!$candidate->effective_to || $candidate->effective_to >= $date)) {
                            $rate = $candidate;
                            break;
                        }
                    }
                    if (!$rate) {
                        foreach ($groups['global'] as $candidate) {
                            if ($candidate->effective_from <= $date &&
                                (!$candidate->effective_to || $candidate->effective_to >= $date)) {
                                $rate = $candidate;
                                break;
                            }
                        }
                    }
                    if ($rate) {
                        $dailyUsageRateCents[$day - 1] += (int) ($rate->rate_cents ?? 0);
                    }
                }
            }
        }

        return view('core::dashboard.utilities.rates', compact(
            'rates',
            'properties',
            'utilityTypes',
            'propertyContext',
            'appliedRates',
            'totalRateCents',
            'totalConsumption',
            'totalBillableUsage',
            'totalBillableCents',
            'filterMonth',
            'filterYear',
            'periodStart',
            'periodEnd',
            'dailyUsageRateCents',
            'dailyConsumption',
            'dailyBillableUsage',
            'dailyBillableCents',
            'electricityTotals',
            'waterTotals',
            'dailyElectricUsage',
            'dailyWaterUsage'
        ));
    }

    public function propertyRates(Request $request, string $tenant, Property $property, CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [UtilityRate::class, $tenant->id]);
        $this->authorize('view', $property);

        $request->merge(['property_id' => $property->id]);

        return $this->index($request, $currentTenant);
    }

    private function resolvePropertyContext(Request $request, int $tenantId): ?Property
    {
        $propertyId = $request->query('property_id');
        if (!$propertyId) {
            return null;
        }

        return Property::query()
            ->where('tenant_id', $tenantId)
            ->find($propertyId);
    }

    public function store(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [UtilityRate::class, $tenant->id]);

        $validated = $request->validate([
            'property_id' => [
                'nullable',
                Rule::exists('properties', 'id')->where('tenant_id', $tenant->id),
            ],
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $rateCents = (int) round(((float) $validated['rate']) * 100);

        $rate = UtilityRate::create([
            'tenant_id' => $tenant->id,
            'property_id' => $validated['property_id'] ?? null,
            'utility_type_id' => $validated['utility_type_id'],
            'rate_cents' => $rateCents,
            'currency_code' => 'USD',
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
        ]);

        $auditLogger->log('created', UtilityRate::class, (string) $rate->id, null, $rate->toArray(), $request);

        return back()->with('status', 'Utility rate created.');
    }

    public function update(Request $request, string $tenant, UtilityRate $rate, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $rate);

        $validated = $request->validate([
            'property_id' => [
                'nullable',
                Rule::exists('properties', 'id')->where('tenant_id', $tenant->id),
            ],
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $before = $rate->toArray();
        $rate->update([
            'property_id' => $validated['property_id'] ?? null,
            'utility_type_id' => $validated['utility_type_id'],
            'rate_cents' => (int) round(((float) $validated['rate']) * 100),
            'currency_code' => 'USD',
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
        ]);

        $auditLogger->log('updated', UtilityRate::class, (string) $rate->id, $before, $rate->toArray(), $request);

        return back()->with('status', 'Utility rate updated.');
    }

    public function destroy(string $tenant, UtilityRate $rate, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('delete', $rate);

        $before = $rate->toArray();
        $rate->delete();

        $auditLogger->log('deleted', UtilityRate::class, (string) $rate->id, $before, null, request());

        return back()->with('status', 'Utility rate deleted.');
    }
}
